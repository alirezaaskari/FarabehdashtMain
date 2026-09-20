<?php

declare(strict_types=1);

namespace App\Support\Measurement;

/**
 * تصویر یک محاسبه ذخیره‌شده، آن‌قدر که ماژول‌های دیگر لازم دارند.
 *
 * در `app/Support` است و نه داخل ماژول ابزارها، تا مصرف‌کننده برای شناختنش
 * به آن ماژول وابسته نشود.
 *
 * `headlineValue` عدد اصلی محاسبه است — همان چیزی که در مقایسه دو دور روی
 * نمودار می‌نشیند. کدام خروجی «اصلی» است را خود ماژول ابزارها تصمیم می‌گیرد،
 * چون فقط او می‌داند هر فرمول چه می‌دهد.
 */
final readonly class StoredCalculation
{
    public function __construct(
        public string $uuid,
        public string $toolSlug,
        public string $formulaId,
        public string $formulaVersion,
        public ?string $label,
        public string $headlineKey,
        public float $headlineValue,
        public string $headlineUnit,
    ) {}
}
