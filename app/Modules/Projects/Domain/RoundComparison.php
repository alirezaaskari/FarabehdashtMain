<?php

declare(strict_types=1);

namespace App\Modules\Projects\Domain;

/**
 * مقایسه دو دور اندازه‌گیری.
 *
 * معیار پذیرش بخش ۸: «مقایسه دو دور جدول و نمودار هم‌زمان می‌دهد.» هر دو از
 * همین یک شیء ساخته می‌شوند تا هیچ‌وقت جدول و نمودار دو چیز متفاوت نگویند.
 *
 * ⚠️ تفسیر کفایت اقدام کنترلی بر عهده کارشناس است؛ این‌جا فقط تغییر عددی
 * گزارش می‌شود.
 */
final readonly class RoundComparison
{
    /**
     * @param  list<ComparisonRow>  $rows
     */
    public function __construct(
        public ProjectRound $before,
        public ProjectRound $after,
        public array $rows,
        public string $unit,
    ) {}

    /**
     * سطرهایی که هر دو دورشان قرائت دارند — تنها سطرهای قابل مقایسه.
     *
     * @return list<ComparisonRow>
     */
    public function comparableRows(): array
    {
        return array_values(array_filter($this->rows, static fn (ComparisonRow $r): bool => $r->complete()));
    }

    public function hasGaps(): bool
    {
        return count($this->comparableRows()) !== count($this->rows);
    }

    /**
     * بیشینه مقدار میان هر دو دور — مبنای مقیاس نمودار.
     *
     * صفر برنمی‌گردد تا تقسیم بر صفر رخ ندهد.
     */
    public function scaleMax(): float
    {
        $values = [];

        foreach ($this->rows as $row) {
            if ($row->before !== null) {
                $values[] = $row->before;
            }

            if ($row->after !== null) {
                $values[] = $row->after;
            }
        }

        $max = $values === [] ? 0.0 : max($values);

        return $max > 0.0 ? $max : 1.0;
    }

    /**
     * میانگین تغییر روی سطرهای کامل، یا null اگر هیچ سطر کاملی نباشد.
     */
    public function averageChange(): ?float
    {
        $rows = $this->comparableRows();

        if ($rows === []) {
            return null;
        }

        $sum = 0.0;

        foreach ($rows as $row) {
            $sum += (float) $row->change();
        }

        return $sum / count($rows);
    }

    public function averagePercentChange(): ?float
    {
        $values = [];

        foreach ($this->comparableRows() as $row) {
            $percent = $row->percentChange();

            if ($percent !== null) {
                $values[] = $percent;
            }
        }

        return $values === [] ? null : array_sum($values) / count($values);
    }
}
