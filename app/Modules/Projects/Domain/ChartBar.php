<?php

declare(strict_types=1);

namespace App\Modules\Projects\Domain;

/**
 * هندسه یک میله در نمودار مقایسه.
 *
 * حساب مختصات این‌جا انجام می‌شود و نه در قالب: قاعده ۴ می‌گوید منطق در
 * Blade نوشته نمی‌شود، و حساب نمودار دقیقاً همان چیزی است که باید تست شود.
 */
final readonly class ChartBar
{
    public function __construct(
        public string $station,
        public int $series,
        public string $seriesLabel,
        public float $value,
        public string $formattedValue,
        public float $x,
        public float $y,
        public float $width,
        public float $height,
    ) {}

    public function chartClass(): string
    {
        return $this->series === 1 ? 'fill-chart-1' : 'fill-chart-2';
    }
}
