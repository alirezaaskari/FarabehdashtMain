<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Formulas\Chemical;

use Farabehdasht\CalcEngine\CrossValidated;
use Farabehdasht\CalcEngine\Formula;
use Farabehdasht\CalcEngine\Input\InputDefinition;
use Farabehdasht\CalcEngine\Input\InputError;
use Farabehdasht\CalcEngine\Input\InputErrorCode;
use Farabehdasht\CalcEngine\Input\InputSet;
use Farabehdasht\CalcEngine\Outcome;
use Farabehdasht\CalcEngine\Reference;
use Farabehdasht\CalcEngine\Unit;

/**
 * شاخص مواجهه با مخلوط، با فرض اثر جمع‌شونده.
 *
 *     EI = Σ(Cᵢ / Lᵢ)
 *
 * `Lᵢ` حد مواجهه‌ای است که کاربر برای هر جزء انتخاب کرده. مثل میانگین
 * وزنی-زمانی، دو نسخه مشخص ثبت می‌شود (ppm و mg/m³) تا واحد ضمنی نماند؛
 * این کلاس فقط ریاضیات مشترک را نگه می‌دارد.
 *
 * سهم بزرگ‌ترین جزء هم برمی‌گردد، چون پرسش بعدی کارشناس معمولاً این است که
 * کنترل را از کجا شروع کند.
 */
abstract readonly class MixtureExposureIndex implements CrossValidated, Formula
{
    private const int MAX_COMPONENTS = 20;

    abstract public function id(): string;

    abstract public function version(): string;

    abstract protected function concentrationUnit(): Unit;

    public function title(): string
    {
        return sprintf('شاخص مواجهه با مخلوط (%s)', $this->concentrationUnit()->symbol());
    }

    public function reference(): Reference
    {
        return new Reference(
            title: 'TLVs and BEIs — Appendix E: Threshold Limit Values for Mixtures',
            publisher: 'کنفرانس دولتی متخصصان بهداشت صنعتی آمریکا (ACGIH)',
            year: 2024,
            relation: 'EI = C₁/L₁ + C₂/L₂ + … + Cₙ/Lₙ',
            note: 'فقط وقتی معتبر است که اجزا بر یک اندام هدف اثر مشابه داشته باشند؛ برای اثرهای مستقل، هر جزء جدا سنجیده می‌شود.',
        );
    }

    public function limitations(): array
    {
        return [
            'رابطه اثر جمع‌شونده را فرض می‌کند؛ اینکه اجزا واقعاً اثر مشابه دارند، قضاوت کارشناس است.',
            'اثر هم‌افزایی یا تقویتی در این رابطه دیده نمی‌شود و ممکن است خطر را کمتر از واقع نشان دهد.',
            'غلظت و حد هر جزء باید از یک نوع باشند (هر دو TWA یا هر دو STEL) و با یک واحد وارد شوند.',
            'مقایسه شاخص با عدد یک، تفسیر است و در خروجی ابزار حکم انطباق نیست.',
        ];
    }

    public function inputs(): array
    {
        $unit = $this->concentrationUnit();

        return [
            'concentrations' => InputDefinition::list('concentrations', 'غلظت هر جزء', $unit, 0.0, 1_000_000.0, 2, self::MAX_COMPONENTS),
            'limits' => InputDefinition::list('limits', 'حد مواجهه هر جزء', $unit, 0.000001, 1_000_000.0, 2, self::MAX_COMPONENTS),
        ];
    }

    public function outputs(): array
    {
        return [
            'exposure_index' => Unit::Ratio,
            'dominant_share' => Unit::Percent,
        ];
    }

    public function crossCheck(InputSet $inputs): array
    {
        $concentrations = $inputs->values('concentrations');
        $limits = $inputs->values('limits');

        if (count($concentrations) === count($limits)) {
            return [];
        }

        return [
            new InputError(
                'limits',
                InputErrorCode::TooFewItems,
                sprintf(
                    'تعداد حدها (%d) با تعداد غلظت‌ها (%d) یکی نیست؛ هر جزء باید هر دو را داشته باشد.',
                    count($limits),
                    count($concentrations),
                ),
                ['expected' => count($concentrations), 'given' => count($limits)],
            ),
        ];
    }

    public function compute(InputSet $inputs): Outcome
    {
        $limits = $inputs->values('limits');

        $ratios = array_map(
            static fn (float $concentration, float $limit): float => $concentration / $limit,
            $inputs->values('concentrations'),
            $limits,
        );

        $index = array_sum($ratios);

        $notes = [];

        if ($index > 0.0 && max($ratios) / $index > 0.8) {
            $notes[] = 'بیش از هشتاد درصد شاخص از یک جزء می‌آید؛ کنترل همان جزء بیشترین اثر را دارد.';
        }

        return new Outcome(
            values: [
                'exposure_index' => $index,
                'dominant_share' => $index > 0.0 ? max($ratios) / $index * 100.0 : 0.0,
            ],
            notes: $notes,
        );
    }
}
