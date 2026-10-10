<?php

declare(strict_types=1);

namespace App\Support\Reporting;

/**
 * شرح یک ارزیابی پوسچر (RULA، REBA، ROSA) برای بخش «ارزیابی ارگونومی» گزارش.
 *
 * امتیازها مثل هر محاسبه دیگر سطرهای جدول نتایج‌اند؛ این‌جا آنچه جدول
 * نمی‌گوید می‌آید: سطح اقدام به زبان ساده، کدام عضو امتیاز را بالا برده و
 * وضعیتی که ارزیاب ثبت کرده، تا خواننده بداند عدد از کجا آمده.
 */
final readonly class ReportAssessment
{
    /**
     * @param  list<string>  $notes  تفسیر نتیجه، همان که صفحه ابزار نشان داد
     * @param  list<array{label: string, value: string}>  $answers  وضعیت ثبت‌شده؛ کلیدهای خاموش نمی‌آیند
     */
    public function __construct(
        public string $method,
        public string $point,
        public array $notes,
        public array $answers,
        public ?string $measuredOn = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        $answers = [];

        foreach ((array) ($data['answers'] ?? []) as $answer) {
            $answer = (array) $answer;
            $answers[] = ['label' => (string) ($answer['label'] ?? ''), 'value' => (string) ($answer['value'] ?? '')];
        }

        return new self(
            method: (string) ($data['method'] ?? ''),
            point: (string) ($data['point'] ?? ''),
            notes: array_values(array_map(strval(...), (array) ($data['notes'] ?? []))),
            answers: $answers,
            measuredOn: isset($data['measuredOn']) ? (string) $data['measuredOn'] : null,
        );
    }
}
