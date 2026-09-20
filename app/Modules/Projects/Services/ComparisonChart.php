<?php

declare(strict_types=1);

namespace App\Modules\Projects\Services;

use App\Modules\Projects\Domain\ChartBar;
use App\Modules\Projects\Domain\RoundComparison;
use App\Support\Measurement\MeasurementNumber;

/**
 * هندسه نمودار میله‌ای جفتی مقایسه دو دور.
 *
 * چند تصمیم که در خودِ SVG دیده نمی‌شوند ولی مهم‌اند:
 *
 * - **ایستگاه‌ها از راست به چپ چیده می‌شوند** تا ترتیب خواندنشان با متن
 *   فارسی یکی باشد. مقدارها ولی لاتین و چپ‌به‌راست می‌مانند.
 * - **فاصله دو پیکسلی بین دو میله هر جفت**، تا مرزشان با رنگ تنها منتقل
 *   نشود. همین فاصله به‌علاوه برچسب عددی روی هر میله، «کدگذاری ثانویه» است
 *   که جدایی رنگ‌ها را برای کوررنگی کافی می‌کند.
 * - **سر میله گرد است و پایه‌اش به خط مبنا چسبیده**، نه یک مستطیل شناور.
 * - مقیاس از بیشینه هر دو دور می‌آید، نه از هر دور جدا؛ وگرنه دو میله با
 *   ارتفاع یکسان دو عدد متفاوت را نشان می‌دادند.
 */
final readonly class ComparisonChart
{
    private const float WIDTH = 720.0;

    private const float HEIGHT = 300.0;

    private const float PAD_TOP = 28.0;

    private const float PAD_BOTTOM = 44.0;

    private const float PAD_SIDE = 16.0;

    private const float BAR_GAP = 2.0;

    private const float GROUP_GAP = 26.0;

    public function width(): float
    {
        return self::WIDTH;
    }

    public function height(): float
    {
        return self::HEIGHT;
    }

    public function baselineY(): float
    {
        return self::HEIGHT - self::PAD_BOTTOM;
    }

    /**
     * خطوط راهنمای افقی — عمداً کم‌رنگ و کم‌تعداد.
     *
     * @return list<array{y: float, label: string}>
     */
    public function gridlines(RoundComparison $comparison): array
    {
        $max = $comparison->scaleMax();
        $plot = $this->baselineY() - self::PAD_TOP;

        $lines = [];

        foreach ([0.0, 0.5, 1.0] as $fraction) {
            $lines[] = [
                'y' => $this->baselineY() - $plot * $fraction,
                'label' => MeasurementNumber::format($max * $fraction),
            ];
        }

        return $lines;
    }

    /**
     * @return list<ChartBar>
     */
    public function bars(RoundComparison $comparison): array
    {
        $rows = $comparison->rows;

        if ($rows === []) {
            return [];
        }

        $max = $comparison->scaleMax();
        $plot = $this->baselineY() - self::PAD_TOP;
        $usable = self::WIDTH - 2 * self::PAD_SIDE;
        $groupWidth = $usable / count($rows);
        $barWidth = max(8.0, ($groupWidth - self::GROUP_GAP - self::BAR_GAP) / 2);

        $bars = [];

        // از راست به چپ، هم‌جهت با متن فارسی.
        foreach (array_reverse($rows) as $index => $row) {
            $groupStart = self::PAD_SIDE + $index * $groupWidth + self::GROUP_GAP / 2;

            foreach ([[1, $row->before, $comparison->before->title], [2, $row->after, $comparison->after->title]] as $offset => $series) {
                [$number, $value, $title] = $series;

                if ($value === null) {
                    continue;
                }

                $height = $max > 0.0 ? $plot * ((float) $value / $max) : 0.0;

                $bars[] = new ChartBar(
                    station: $row->station,
                    series: (int) $number,
                    seriesLabel: (string) $title,
                    value: (float) $value,
                    formattedValue: MeasurementNumber::format((float) $value),
                    x: $groupStart + $offset * ($barWidth + self::BAR_GAP),
                    y: $this->baselineY() - $height,
                    width: $barWidth,
                    height: $height,
                );
            }
        }

        return $bars;
    }

    /**
     * برچسب ایستگاه‌ها، وسط هر گروه.
     *
     * @return list<array{x: float, label: string}>
     */
    public function stationLabels(RoundComparison $comparison): array
    {
        $rows = $comparison->rows;

        if ($rows === []) {
            return [];
        }

        $usable = self::WIDTH - 2 * self::PAD_SIDE;
        $groupWidth = $usable / count($rows);

        $labels = [];

        foreach (array_reverse($rows) as $index => $row) {
            $labels[] = [
                'x' => self::PAD_SIDE + $index * $groupWidth + $groupWidth / 2,
                'label' => $row->station,
            ];
        }

        return $labels;
    }
}
