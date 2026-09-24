<?php

declare(strict_types=1);

namespace App\Support\Workspace;

/**
 * عدد برجسته یک کارت میزکار.
 *
 * `value` از قبل قالب‌بندی شده است (مبلغ با `Money::format()`، شمار با ارقام
 * فارسی)؛ قالب فقط چاپش می‌کند.
 */
final readonly class WidgetStat
{
    public function __construct(
        public string $label,
        public string $value,
    ) {}
}
