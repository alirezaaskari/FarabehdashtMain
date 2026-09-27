<?php

declare(strict_types=1);

namespace App\Support\Settings;

use InvalidArgumentException;

/**
 * یک قیمت یا مدت قابل‌تغییر از پنل.
 *
 * `key` همان کلید config ماژول است (مثل `consulting.orders.reply_hours`) و
 * مقدار config پیش‌فرض می‌ماند تا مدیر عددی ذخیره کند. `min` و `max` جلوی عددی
 * را می‌گیرند که سایت را می‌شکند (قیمت صفر، مهلت صفر ساعت).
 */
final readonly class Tunable
{
    public function __construct(
        public string $key,
        public string $section,
        public string $label,
        public TunableUnit $unit,
        public int $min,
        public int $max,
        public string $hint = '',
    ) {
        if ($min > $max) {
            throw new InvalidArgumentException('کمینه '.$key.' از بیشینه‌اش بزرگ‌تر است.');
        }
    }

    public function accepts(int $value): bool
    {
        return $value >= $this->min && $value <= $this->max;
    }
}
