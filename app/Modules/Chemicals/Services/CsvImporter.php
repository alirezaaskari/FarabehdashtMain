<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Services;

use App\Modules\Chemicals\Domain\CasNumber;
use App\Modules\Chemicals\Domain\Import\FieldChange;
use App\Modules\Chemicals\Domain\Import\ImportAction;
use App\Modules\Chemicals\Domain\Import\ImportPlan;
use App\Modules\Chemicals\Domain\Import\ImportRowResult;
use App\Modules\Chemicals\Domain\Substance;
use App\Support\PersianDigits;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * ساخت گزارش تغییرات از یک فایل CSV — بدون نوشتن چیزی در پایگاه داده.
 *
 * تشخیص «تازه» یا «به‌روزرسانی» روی **شماره CAS** است، نه نام: نام فارسی
 * ماده‌ای می‌تواند در دو فایل مختلف نوشته شود («تولوئن» / «تولوئول») ولی
 * CAS یکتا و بدون ابهام است.
 *
 * این کلاس فقط می‌خواند. نوشتن کار `Actions\ApplyCsvImport` است — تا آنچه
 * مدیر در پیش‌نمایش تأیید می‌کند، دقیقاً همان چیزی باشد که روی دیسک می‌رود.
 */
final readonly class CsvImporter
{
    /**
     * @param  list<string>  $expectedColumns
     */
    public function __construct(
        private array $expectedColumns,
        private int $maxRows,
    ) {}

    /**
     * @throws InvalidArgumentException اگر سربرگ ستون‌ها نامعتبر باشد یا فایل خالی باشد
     */
    public function plan(string $csvContents): ImportPlan
    {
        $rows = $this->parse($csvContents);

        if ($rows === []) {
            throw new InvalidArgumentException('فایل CSV هیچ ردیفی ندارد.');
        }

        if (count($rows) > $this->maxRows) {
            throw new InvalidArgumentException(sprintf(
                'فایل %s ردیف دارد؛ سقف مجاز %s ردیف در هر بار است.',
                PersianDigits::from(count($rows)),
                PersianDigits::from($this->maxRows),
            ));
        }

        $existingByCas = Substance::query()
            ->get()
            ->keyBy(static fn (Substance $s): string => $s->cas_number);

        $results = [];
        $seenInFile = [];

        foreach ($rows as $line => $data) {
            $result = $this->evaluateRow($line, $data, $existingByCas, $seenInFile);
            $results[] = $result;

            if ($result->action !== ImportAction::Invalid) {
                $seenInFile[$data['cas_number']] = true;
            }
        }

        return new ImportPlan($results);
    }

    /**
     * @param  array<string, string>  $data
     * @param  Collection<string, Substance>  $existingByCas
     * @param  array<string, true>  $seenInFile
     */
    private function evaluateRow(int $line, array $data, $existingByCas, array $seenInFile): ImportRowResult
    {
        $casRaw = trim($data['cas_number'] ?? '');

        if ($casRaw === '' || trim($data['name_fa'] ?? '') === '' || trim($data['name_en'] ?? '') === '') {
            return new ImportRowResult($line, ImportAction::Invalid, $data, reason: 'شماره CAS، نام فارسی و نام انگلیسی هر سه الزامی‌اند.');
        }

        if (! CasNumber::isValid($casRaw)) {
            return new ImportRowResult($line, ImportAction::Invalid, $data, reason: "شماره CAS «{$casRaw}» رقم کنترلی نمی‌خواند؛ احتمالاً اشتباه تایپی است.");
        }

        $cas = (string) CasNumber::fromString($casRaw);
        $data['cas_number'] = $cas;

        if (isset($seenInFile[$cas])) {
            return new ImportRowResult($line, ImportAction::Invalid, $data, reason: "شماره CAS «{$cas}» در همین فایل تکراری است.");
        }

        if (isset($data['molar_mass']) && trim((string) $data['molar_mass']) !== '') {
            $molarMass = trim((string) $data['molar_mass']);

            if (! is_numeric($molarMass) || (float) $molarMass <= 0) {
                return new ImportRowResult($line, ImportAction::Invalid, $data, reason: "جرم مولکولی «{$molarMass}» یک عدد مثبت نیست.");
            }
        }

        $existing = $existingByCas->get($cas);

        if ($existing === null) {
            return new ImportRowResult($line, ImportAction::Create, $data);
        }

        $changes = $this->diff($existing, $data);

        return $changes === []
            ? new ImportRowResult($line, ImportAction::Unchanged, $data)
            : new ImportRowResult($line, ImportAction::Update, $data, $changes);
    }

    /**
     * @param  array<string, string>  $data
     * @return list<FieldChange>
     */
    private function diff(Substance $existing, array $data): array
    {
        $labels = [
            'name_fa' => 'نام فارسی',
            'name_en' => 'نام انگلیسی',
            'formula' => 'فرمول',
            'molar_mass' => 'جرم مولکولی',
            'physical_state' => 'حالت فیزیکی',
        ];

        $changes = [];

        foreach ($labels as $field => $label) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $incoming = trim((string) $data[$field]);
            $incoming = $incoming === '' ? null : $incoming;

            $current = $existing->{$field};
            $current = $current === null ? null : (string) $current;

            if ($incoming !== $current) {
                $changes[] = new FieldChange($field, $label, $current, $incoming);
            }
        }

        return $changes;
    }

    /**
     * @return array<int, array<string, string>> شماره خط (از ۲، چون خط ۱ سربرگ است) => ردیف
     *
     * @throws InvalidArgumentException
     */
    private function parse(string $csvContents): array
    {
        // «CSV UTF-8» اکسل فایل را با BOM شروع می‌کند؛ خروجی خود سایت هم.
        $csvContents = preg_replace('/^\x{FEFF}/u', '', $csvContents) ?? $csvContents;

        $lines = preg_split('/\r\n|\r|\n/', trim($csvContents)) ?: [];
        $lines = array_values(array_filter($lines, static fn (string $l): bool => trim($l) !== ''));

        if ($lines === []) {
            return [];
        }

        $headerLine = (string) array_shift($lines);
        $delimiter = self::delimiter($headerLine);

        $header = str_getcsv($headerLine, $delimiter, '"', '');
        $header = array_map(static fn (mixed $h): string => strtolower(trim((string) $h)), $header);

        if ($header !== $this->expectedColumns) {
            throw new InvalidArgumentException(sprintf(
                'سربرگ ستون‌ها نمی‌خواند. ردیف اول فایل باید دقیقاً این‌ها باشد: %s',
                implode('، ', $this->expectedColumns),
            ));
        }

        $rows = [];

        foreach ($lines as $index => $line) {
            $values = str_getcsv($line, $delimiter, '"', '');
            $row = [];

            foreach ($this->expectedColumns as $position => $column) {
                $row[$column] = trim((string) ($values[$position] ?? ''));
            }

            // اکسل با تنظیمات فارسی عدد را با ارقام و ممیز فارسی می‌نویسد.
            foreach (['cas_number', 'molar_mass'] as $numeric) {
                if (isset($row[$numeric])) {
                    $row[$numeric] = str_replace('٫', '.', PersianDigits::toLatin($row[$numeric]));
                }
            }

            $rows[$index + 2] = $row;
        }

        return $rows;
    }

    /**
     * جداکننده از روی سربرگ: اکسل در بعضی تنظیمات منطقه‌ای به‌جای ویرگول
     * نقطه‌ویرگول می‌گذارد و «متن جداشده با تب» هم رایج است.
     */
    private static function delimiter(string $headerLine): string
    {
        $counts = [',' => substr_count($headerLine, ','), ';' => substr_count($headerLine, ';'), "\t" => substr_count($headerLine, "\t")];
        arsort($counts);

        return (string) array_key_first($counts);
    }
}
