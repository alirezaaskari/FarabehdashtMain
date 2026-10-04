<?php

declare(strict_types=1);

namespace App\Support\Market;

use App\Support\PersianNumber;

/**
 * سابقه یک مشاور یا آزمایشگاه در بازار پروژه، برای صفحه عمومی‌اش (بخش ۲۱-۶).
 *
 * `average` فقط وقتی هست که امتیازهای دیده‌شدنی به کمینه رسیده باشد (DEC-85).
 */
final readonly class ProviderRecord
{
    /** @param  list<RatingQuote>  $quotes  تازه‌ترین اول */
    public function __construct(
        public int $completed,
        public int $ratings,
        public ?float $average,
        public int $averageMin,
        public array $quotes = [],
    ) {}

    /** «۴٫۶» یا برای عدد درست «۴». */
    public function averageLabel(): ?string
    {
        if ($this->average === null) {
            return null;
        }

        return PersianNumber::decimal($this->average, fmod($this->average, 1.0) === 0.0 ? 0 : 1);
    }

    public function isEmpty(): bool
    {
        return $this->completed === 0 && $this->ratings === 0;
    }
}
