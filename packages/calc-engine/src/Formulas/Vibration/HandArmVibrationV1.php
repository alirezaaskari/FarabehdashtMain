<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Formulas\Vibration;

use Farabehdasht\CalcEngine\CrossValidated;
use Farabehdasht\CalcEngine\Formula;
use Farabehdasht\CalcEngine\Input\InputDefinition;
use Farabehdasht\CalcEngine\Input\InputSet;
use Farabehdasht\CalcEngine\Outcome;
use Farabehdasht\CalcEngine\Reference;
use Farabehdasht\CalcEngine\Unit;

/**
 * مواجهه روزانه با ارتعاش دست و بازو، نرمال‌شده به هشت ساعت.
 *
 *     A(8) = √( Σ a_hv,i² × Tᵢ / T₀ ) ، T₀ = 8 h
 *
 * `a_hv` مقدار کل ارتعاش (جمع برداری سه محور وزن‌دار) هر ابزار است. اگر فقط
 * قرائت سه محور را دارید، اول √(ax² + ay² + az²) را حساب کنید.
 */
final readonly class HandArmVibrationV1 implements CrossValidated, Formula
{
    private const int MAX_OPERATIONS = 24;

    private const float REFERENCE_HOURS = 8.0;

    public function id(): string
    {
        return 'hand-arm-vibration';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function title(): string
    {
        return 'مواجهه روزانه با ارتعاش دست و بازو A(8)';
    }

    public function reference(): Reference
    {
        return new Reference(
            title: 'ISO 5349-1 — Mechanical vibration: Measurement and evaluation of human exposure to hand-transmitted vibration',
            publisher: 'سازمان بین‌المللی استانداردسازی (ISO)',
            year: 2001,
            relation: 'A(8) = √(Σ a_hv,i² · Tᵢ / 8)',
            note: 'a_hv مقدار کل ارتعاش وزن‌دار فرکانسی (Wh) است: جمع برداری سه محور.',
        );
    }

    public function limitations(): array
    {
        return [
            'مقدار ارتعاش اعلام‌شده سازنده معمولاً کمتر از مقدار واقعی در کار است؛ اندازه‌گیری در محل قابل اتکاتر است.',
            'مدت هر کار باید زمان واقعی تماس دست با ابزار در حال ارتعاش باشد، نه مدت کل کار.',
            'سرما، نیروی گرفتن و وضعیت دست که بر اثر ارتعاش می‌افزایند، در این عدد نیستند.',
            'مقایسه A(8) با مقدار اقدام یا حد مواجهه، تفسیر است و در خروجی ابزار انجام نمی‌شود.',
        ];
    }

    public function inputs(): array
    {
        return [
            'magnitudes' => InputDefinition::list('magnitudes', 'مقدار کل ارتعاش هر ابزار', Unit::MetrePerSecondSquared, 0.0, 100.0, 1, self::MAX_OPERATIONS),
            'durations' => InputDefinition::list('durations', 'مدت تماس روزانه', Unit::Hour, 0.0, 24.0, 1, self::MAX_OPERATIONS),
        ];
    }

    public function outputs(): array
    {
        return [
            'daily_exposure' => Unit::MetrePerSecondSquared,
            'covered_duration' => Unit::Hour,
        ];
    }

    public function crossCheck(InputSet $inputs): array
    {
        return Durations::check($inputs, 'magnitudes', 'مقدارها');
    }

    public function compute(InputSet $inputs): Outcome
    {
        $durations = $inputs->values('durations');

        $sum = 0.0;

        foreach ($inputs->values('magnitudes') as $index => $magnitude) {
            $sum += $magnitude ** 2 * $durations[$index];
        }

        return new Outcome(
            values: [
                'daily_exposure' => sqrt($sum / self::REFERENCE_HOURS),
                'covered_duration' => array_sum($durations),
            ],
        );
    }
}
