<?php

declare(strict_types=1);

namespace App\Modules\Projects\Domain;

/**
 * یک سطر مقایسه: یک ایستگاه، در دو دور.
 *
 * `change` و `percentChange` فقط تفاوت عددی‌اند. **تفسیر کفایت اقدام کنترلی
 * بر عهده کارشناس است**؛ این کلاس هرگز نمی‌گوید تغییر «خوب» یا «کافی» بوده.
 */
final readonly class ComparisonRow
{
    public function __construct(
        public string $station,
        public ?float $before,
        public ?float $after,
        public string $unit,
    ) {}

    public function complete(): bool
    {
        return $this->before !== null && $this->after !== null;
    }

    public function change(): ?float
    {
        return $this->complete() ? (float) $this->after - (float) $this->before : null;
    }

    public function percentChange(): ?float
    {
        if (! $this->complete() || $this->before === null || $this->before === 0.0) {
            return null;
        }

        return ((float) $this->after - $this->before) / abs($this->before) * 100;
    }

    /**
     * جهت تغییر — بدون قضاوت درباره مطلوب‌بودنش.
     *
     * برای صدا و غلظت کاهش مطلوب است، برای روشنایی معمولاً افزایش. پس
     * «بهتر» و «بدتر» این‌جا تصمیم‌گیری نمی‌شود.
     */
    public function direction(): string
    {
        $change = $this->change();

        return match (true) {
            $change === null => 'unknown',
            abs($change) < 1e-9 => 'unchanged',
            $change < 0 => 'decreased',
            default => 'increased',
        };
    }
}
