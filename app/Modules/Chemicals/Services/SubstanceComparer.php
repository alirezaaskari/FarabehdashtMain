<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Services;

use App\Modules\Chemicals\Domain\ComparisonRow;
use App\Modules\Chemicals\Domain\Enums\FactKind;
use App\Modules\Chemicals\Domain\Enums\LimitType;
use App\Modules\Chemicals\Domain\Substance;
use App\Support\Measurement\MeasurementNumber;

/**
 * جدول مقایسه چند ماده.
 *
 * برخلاف پروتوتایپ که ستون TWA را فقط از ACGIH می‌خواند، این‌جا هر نوع حدی
 * که **دست‌کم یکی از مواد** دارد یک ردیف می‌گیرد و مقدار از اولین مرجع
 * موجود (به ترتیب اولویت مرجع ایرانی، سپس ACGIH و…) خوانده می‌شود. تصمیم
 * آگاهانه است: بانک ما چندمرجعی است، پس مقایسه تک‌مرجعی داده واقعی را پنهان
 * می‌کند.
 */
final readonly class SubstanceComparer
{
    /**
     * @param  list<Substance>  $substances
     * @return list<ComparisonRow>
     */
    public function compare(array $substances): array
    {
        $rows = [
            new ComparisonRow('نام انگلیسی', $this->map($substances, static fn (Substance $s): string => $s->name_en)),
            new ComparisonRow('شماره CAS', $this->map($substances, static fn (Substance $s): string => $s->cas_number), numeric: true),
            new ComparisonRow('فرمول', $this->map($substances, static fn (Substance $s): string => $s->formula ?? '—')),
            new ComparisonRow(
                'جرم مولکولی',
                $this->map($substances, static fn (Substance $s): string => $s->molar_mass === null
                    ? '—'
                    : MeasurementNumber::format($s->molar_mass, 2).' g/mol'),
                numeric: true,
            ),
        ];

        foreach (LimitType::cases() as $type) {
            if (! $this->anyHas($substances, $type)) {
                continue;
            }

            $rows[] = new ComparisonRow(
                $type->label(),
                $this->map($substances, fn (Substance $s): string => $this->limitCell($s, $type)),
                numeric: true,
            );
        }

        $rows[] = new ComparisonRow(
            'مسیرهای مواجهه',
            $this->map($substances, fn (Substance $s): string => $this->factsCell($s, FactKind::Route)),
        );

        $rows[] = new ComparisonRow(
            'رسانه نمونه‌برداری',
            $this->map($substances, static fn (Substance $s): string => $s->sampling_media ?? '—'),
        );

        $rows[] = new ComparisonRow(
            'روش تحلیل',
            $this->map($substances, static fn (Substance $s): string => $s->analysis_method ?? '—'),
        );

        $rows[] = new ComparisonRow(
            'حفاظت فردی',
            $this->map($substances, fn (Substance $s): string => $this->factsCell($s, FactKind::Protection)),
        );

        return $rows;
    }

    /**
     * @param  list<Substance>  $substances
     * @param  callable(Substance): string  $fn
     * @return list<string>
     */
    private function map(array $substances, callable $fn): array
    {
        return array_map($fn, $substances);
    }

    /** @param  list<Substance>  $substances */
    private function anyHas(array $substances, LimitType $type): bool
    {
        foreach ($substances as $substance) {
            foreach ($substance->limits as $limit) {
                if ($limit->type === $type) {
                    return true;
                }
            }
        }

        return false;
    }

    private function limitCell(Substance $substance, LimitType $type): string
    {
        foreach ($substance->orderedLimits() as $limit) {
            if ($limit->type === $type) {
                return $limit->formattedValue().' '.$limit->unit;
            }
        }

        return '—';
    }

    private function factsCell(Substance $substance, FactKind $kind): string
    {
        $facts = $substance->factsOf($kind);

        return $facts->isEmpty() ? '—' : $facts->pluck('text')->implode(' · ');
    }
}
