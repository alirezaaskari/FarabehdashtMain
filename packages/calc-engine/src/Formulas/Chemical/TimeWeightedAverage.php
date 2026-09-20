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
 * میانگین وزنی-زمانی مواجهه.
 *
 *     TWA = Σ(Cᵢ × tᵢ) / Σtᵢ
 *
 * ریاضیاتش به واحد غلظت کار ندارد، ولی موتور واحد را ضمنی رها نمی‌کند: دو
 * نسخه‌ی مشخص از این رابطه ثبت می‌شود، یکی برای ppm و یکی برای mg/m³، و هر
 * کدام مستقل نسخه می‌گیرد. این کلاس فقط ریاضیات مشترک را نگه می‌دارد.
 *
 * عمداً «TWA هشت‌ساعته» نیست. اگر مدت نمونه‌برداری کمتر از شیفت باشد، دو
 * قرارداد متفاوت وجود دارد (صفر گرفتن زمان نمونه‌برداری‌نشده، یا تعمیم
 * میانگین به کل شیفت) و انتخاب بینشان قضاوت کارشناس است، نه کار موتور.
 * خروجی، مدت پوشش‌داده‌شده را هم برمی‌گرداند تا این انتخاب آگاهانه بماند.
 */
abstract readonly class TimeWeightedAverage implements CrossValidated, Formula
{
    private const int MAX_SAMPLES = 48;

    abstract public function id(): string;

    abstract public function version(): string;

    abstract protected function concentrationUnit(): Unit;

    public function title(): string
    {
        return sprintf('میانگین وزنی-زمانی مواجهه (%s)', $this->concentrationUnit()->symbol());
    }

    public function reference(): Reference
    {
        return new Reference(
            title: 'تعریف میانگین وزنی-زمانی مواجهه شغلی',
            publisher: 'کنفرانس دولتی متخصصان بهداشت صنعتی آمریکا (ACGIH)',
            year: 2024,
            relation: 'TWA = Σ(Cᵢ × tᵢ) / Σtᵢ',
            note: 'رابطه، میانگین وزنیِ غلظت بر حسب مدت است و در متون بهداشت حرفه‌ای استاندارد است.',
        );
    }

    public function limitations(): array
    {
        return [
            'خروجی میانگین همان بازه‌ای است که نمونه‌برداری شده، نه لزوماً TWA هشت‌ساعته. اگر بازه کوتاه‌تر از شیفت باشد، تعمیم دادنش قضاوت کارشناس است.',
            'مقایسه این عدد با حد مجاز مواجهه، تفسیر است و در خروجی ابزار انجام نمی‌شود.',
            'رابطه فرض می‌کند غلظت در هر بازه ثابت بوده است؛ نوسان درون یک بازه دیده نمی‌شود.',
        ];
    }

    public function inputs(): array
    {
        return [
            'concentrations' => InputDefinition::list(
                'concentrations',
                'غلظت هر بازه',
                $this->concentrationUnit(),
                0.0,
                1_000_000.0,
                1,
                self::MAX_SAMPLES,
            ),
            'durations' => InputDefinition::list(
                'durations',
                'مدت هر بازه',
                Unit::Minute,
                0.0,
                1_440.0,
                1,
                self::MAX_SAMPLES,
            ),
        ];
    }

    public function outputs(): array
    {
        return [
            'twa' => $this->concentrationUnit(),
            'covered_duration' => Unit::Minute,
        ];
    }

    public function crossCheck(InputSet $inputs): array
    {
        $concentrations = $inputs->values('concentrations');
        $durations = $inputs->values('durations');

        $errors = [];

        if (count($concentrations) !== count($durations)) {
            $errors[] = new InputError(
                'durations',
                InputErrorCode::TooFewItems,
                sprintf(
                    'تعداد مدت‌ها (%d) با تعداد غلظت‌ها (%d) یکی نیست؛ هر بازه باید هر دو را داشته باشد.',
                    count($durations),
                    count($concentrations),
                ),
                ['expected' => count($concentrations), 'given' => count($durations)],
            );
        }

        if (array_sum($durations) <= 0.0) {
            $errors[] = new InputError(
                'durations',
                InputErrorCode::OutOfRange,
                'مجموع مدت بازه‌ها باید بیشتر از صفر باشد.',
                ['given' => array_sum($durations)],
            );
        }

        return $errors;
    }

    public function compute(InputSet $inputs): Outcome
    {
        $concentrations = $inputs->values('concentrations');
        $durations = $inputs->values('durations');

        $weighted = 0.0;

        foreach ($concentrations as $index => $concentration) {
            $weighted += $concentration * $durations[$index];
        }

        $total = array_sum($durations);

        $notes = [];

        if ($total < 480.0) {
            $notes[] = 'مدت نمونه‌برداری کمتر از یک شیفت هشت‌ساعته است؛ این عدد میانگین همان بازه است، نه TWA هشت‌ساعته.';
        }

        return new Outcome(
            values: [
                'twa' => $weighted / $total,
                'covered_duration' => $total,
            ],
            notes: $notes,
        );
    }
}
