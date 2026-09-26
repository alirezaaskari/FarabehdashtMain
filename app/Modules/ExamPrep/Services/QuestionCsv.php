<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Services;

use App\Support\PersianDigits;
use InvalidArgumentException;

/**
 * خواندن و ساختن فایل CSV سؤال‌ها برای ورود دسته‌ای از پنل.
 *
 * هر ردیف یک سؤال است. ردیف نامعتبر با شماره خط و دلیلش برمی‌گردد تا مدیر
 * پیش از اجرا ببیند چه چیزی نوشته نمی‌شود. خانه چندخطی داخل «"…"» مجاز است.
 */
final readonly class QuestionCsv
{
    public const COLUMNS = [
        'topic', 'difficulty', 'question',
        'choice_1', 'choice_2', 'choice_3', 'choice_4', 'choice_5',
        'correct', 'explanation', 'reference_label', 'reference_path', 'sample',
    ];

    public function __construct(private int $maxRows) {}

    /**
     * @return list<array{line: int, draft: ?QuestionDraft, error: ?string}>
     */
    public function read(string $contents): array
    {
        $contents = preg_replace('/^\x{FEFF}/u', '', $contents) ?? $contents;
        $firstLine = strtok($contents, "\r\n") ?: '';
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new InvalidArgumentException('فایل خوانده نشد.');
        }

        fwrite($stream, $contents);
        rewind($stream);

        $header = fgetcsv($stream, null, $delimiter, '"', '');
        $header = array_map(static fn (mixed $h): string => strtolower(trim((string) $h)), $header ?: []);

        if ($header !== self::COLUMNS) {
            fclose($stream);

            throw new InvalidArgumentException('سربرگ ستون‌ها نمی‌خواند. ردیف اول فایل باید دقیقاً این‌ها باشد: '.implode('، ', self::COLUMNS));
        }

        $rows = [];
        $line = 1;

        while (($values = fgetcsv($stream, null, $delimiter, '"', '')) !== false) {
            $line++;

            if ($values === [null] || implode('', array_map('strval', $values)) === '') {
                continue;
            }

            if (count($rows) >= $this->maxRows) {
                fclose($stream);

                throw new InvalidArgumentException(sprintf('هر بار حداکثر %d سؤال؛ فایل را چند تکه کنید.', $this->maxRows));
            }

            $cell = static fn (string $column): string => trim((string) ($values[array_search($column, self::COLUMNS, true)] ?? ''));

            try {
                $rows[] = ['line' => $line, 'draft' => QuestionDraft::make(
                    topic: $cell('topic'),
                    difficulty: $cell('difficulty'),
                    body: $cell('question'),
                    choices: array_map($cell, ['choice_1', 'choice_2', 'choice_3', 'choice_4', 'choice_5']),
                    correct: (int) PersianDigits::toLatin($cell('correct')),
                    explanation: $cell('explanation'),
                    referenceLabel: $cell('reference_label'),
                    referencePath: $cell('reference_path'),
                    isSample: in_array(mb_strtolower(PersianDigits::toLatin($cell('sample'))), ['1', 'yes', 'بله', 'true'], true),
                ), 'error' => null];
            } catch (InvalidArgumentException $exception) {
                $rows[] = ['line' => $line, 'draft' => null, 'error' => $exception->getMessage()];
            }
        }

        fclose($stream);

        if ($rows === []) {
            throw new InvalidArgumentException('فایل CSV هیچ سؤالی ندارد.');
        }

        return $rows;
    }

    /** قالب خالی با دو ردیف نمونه، با BOM تا اکسل فارسی را درست باز کند. */
    public function template(): string
    {
        $rows = [
            self::COLUMNS,
            ['سم‌شناسی', 'متوسط', 'حد مجاز مواجهه شغلی میانگین وزنی زمانی برای چند ساعت کار روزانه تعریف می‌شود؟',
                '۴ ساعت', '۸ ساعت', '۱۰ ساعت', '۱۲ ساعت', '', '2',
                'TWA برای ۸ ساعت کار روزانه و ۴۰ ساعت در هفته تعریف می‌شود.', 'حدود مجاز مواجهه', '/encyclopedia', '1'],
            ['صدا', 'آسان', 'واحد تراز فشار صوت کدام است؟', 'دسی‌بل', 'هرتز', 'پاسکال', '', '', '1', '', '', '', '0'],
        ];

        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            return '';
        }

        foreach ($rows as $row) {
            fputcsv($stream, $row, ',', '"', '');
        }

        rewind($stream);
        $csv = (string) stream_get_contents($stream);
        fclose($stream);

        return "\u{FEFF}".$csv;
    }
}
