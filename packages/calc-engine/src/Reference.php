<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine;

/**
 * منبع رابطه‌ای که یک فرمول پیاده می‌کند.
 *
 * ذکر منبع اختیاری نیست: هر فرمول باید بگوید عددش از کجا می‌آید، وگرنه خروجی
 * ابزار یک ادعای بی‌پشتوانه است.
 */
final readonly class Reference
{
    public function __construct(
        public string $title,
        public string $publisher,
        public int $year,
        public string $relation,
        public string $note = '',
    ) {}

    /**
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'publisher' => $this->publisher,
            'year' => $this->year,
            'relation' => $this->relation,
            'note' => $this->note,
        ];
    }
}
