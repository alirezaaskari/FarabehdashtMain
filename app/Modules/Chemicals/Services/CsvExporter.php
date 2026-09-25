<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Services;

use App\Modules\Chemicals\Domain\Substance;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * خروجی CSV بانک مواد.
 *
 * ستون‌ها دقیقاً همان‌هایی هستند که `CsvImporter` می‌خواند — تا فایلی که از
 * این سایت خارج شده، بدون تغییر ستون دوباره وارد همین سایت شود.
 */
final readonly class CsvExporter
{
    /** @param  list<string>  $columns */
    public function __construct(private array $columns) {}

    /** @param  Collection<int, Substance>  $substances */
    public function export(Collection $substances): string
    {
        $lines = [$this->csvLine($this->columns)];

        foreach ($substances as $substance) {
            $lines[] = $this->csvLine(array_map(
                static fn (string $column): string => (string) ($substance->{$column} ?? ''),
                $this->columns,
            ));
        }

        // BOM برای اکسل فارسی: بدون آن، نام‌های فارسی در اکسل رمزگشایی
        // نادرست نشان داده می‌شوند.
        return "\u{FEFF}".implode("\r\n", $lines)."\r\n";
    }

    /**
     * قالب خالی برای پرکردن در اکسل: سربرگ و دو ردیف نمونه واقعی که خود
     * صفحه راهنما توضیحشان می‌دهد. ردیف‌های نمونه را باید پیش از ورود پاک کرد
     * یا نگه داشت؛ هر دو ماده CAS درست دارند.
     */
    public function template(): string
    {
        $samples = [
            ['cas_number' => '108-88-3', 'name_fa' => 'تولوئن', 'name_en' => 'Toluene', 'formula' => 'C7H8', 'molar_mass' => '92.14', 'physical_state' => 'مایع'],
            ['cas_number' => '7664-41-7', 'name_fa' => 'آمونیاک', 'name_en' => 'Ammonia', 'formula' => 'NH3', 'molar_mass' => '17.03', 'physical_state' => 'گاز'],
        ];

        $lines = [$this->csvLine($this->columns)];

        foreach ($samples as $sample) {
            $lines[] = $this->csvLine(array_map(static fn (string $column): string => $sample[$column] ?? '', $this->columns));
        }

        return "\u{FEFF}".implode("\r\n", $lines)."\r\n";
    }

    /** @param  list<string>  $fields */
    private function csvLine(array $fields): string
    {
        $handle = fopen('php://memory', 'w+');

        if ($handle === false) {
            throw new RuntimeException('امکان ساخت بافر موقت برای CSV نبود.');
        }

        fputcsv($handle, $fields, ',', '"', '');
        rewind($handle);
        $line = rtrim((string) stream_get_contents($handle), "\r\n");
        fclose($handle);

        return $line;
    }
}
