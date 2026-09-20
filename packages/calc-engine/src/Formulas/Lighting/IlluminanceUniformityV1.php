<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Formulas\Lighting;

use Farabehdasht\CalcEngine\Formula;
use Farabehdasht\CalcEngine\Input\InputDefinition;
use Farabehdasht\CalcEngine\Input\InputSet;
use Farabehdasht\CalcEngine\Outcome;
use Farabehdasht\CalcEngine\Reference;
use Farabehdasht\CalcEngine\Unit;

/**
 * روشنایی متوسط و یکنواختی، از قرائت‌های شبکه‌بندی‌شده.
 *
 *     E_avg = Σ Eᵢ / n
 *     U₀    = E_min / E_avg
 *     U_d   = E_min / E_max
 *
 * دو نسبت یکنواختی هر دو برگردانده می‌شوند چون مراجع مختلف یکی از آن دو را
 * می‌خواهند و انتخاب بینشان کار کارشناس است، نه ابزار.
 */
final readonly class IlluminanceUniformityV1 implements Formula
{
    private const int MAX_POINTS = 200;

    public function id(): string
    {
        return 'illuminance-uniformity';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function title(): string
    {
        return 'روشنایی متوسط و یکنواختی';
    }

    public function reference(): Reference
    {
        return new Reference(
            title: 'EN 12464-1 — روشنایی محل کار',
            publisher: 'کمیته اروپایی استانداردسازی (CEN)',
            year: 2021,
            relation: 'E_avg = Σ Eᵢ / n ، U₀ = E_min / E_avg ، U_d = E_min / E_max',
            note: 'میانگین حسابی قرائت‌های شبکه و دو نسبت یکنواختی مرسوم. چگونگی شبکه‌بندی بیرون از این رابطه است.',
        );
    }

    public function limitations(): array
    {
        return [
            'درستی نتیجه کاملاً به شبکه‌بندی وابسته است: تعداد و جای نقاط را این رابطه تعیین نمی‌کند.',
            'میانگین حسابی است و فرض می‌کند نقاط سطح یکسانی را نمایندگی می‌کنند؛ شبکه نامنظم نتیجه را سوگیر می‌کند.',
            'مقایسه با حد توصیه‌شده روشنایی هر وظیفه، تفسیر است و در خروجی ابزار انجام نمی‌شود.',
        ];
    }

    public function inputs(): array
    {
        return [
            'readings' => InputDefinition::list('readings', 'قرائت‌های روشنایی', Unit::Lux, 0.0, 200_000.0, 2, self::MAX_POINTS),
        ];
    }

    public function outputs(): array
    {
        return [
            'average' => Unit::Lux,
            'minimum' => Unit::Lux,
            'maximum' => Unit::Lux,
            'uniformity_min_average' => Unit::Ratio,
            'uniformity_min_max' => Unit::Ratio,
        ];
    }

    public function compute(InputSet $inputs): Outcome
    {
        $readings = $inputs->values('readings');

        $average = array_sum($readings) / count($readings);
        $minimum = min($readings);
        $maximum = max($readings);

        $notes = [];

        if ($average <= 0.0) {
            // همه قرائت‌ها صفر: نسبت یکنواختی تعریف ندارد و صفر گرفتنش دروغ است.
            $notes[] = 'همه قرائت‌ها صفرند؛ نسبت یکنواختی در این حالت تعریف نمی‌شود و صفر گزارش شده است.';
        }

        return new Outcome(
            values: [
                'average' => $average,
                'minimum' => $minimum,
                'maximum' => $maximum,
                'uniformity_min_average' => $average > 0.0 ? $minimum / $average : 0.0,
                'uniformity_min_max' => $maximum > 0.0 ? $minimum / $maximum : 0.0,
            ],
            notes: $notes,
        );
    }
}
