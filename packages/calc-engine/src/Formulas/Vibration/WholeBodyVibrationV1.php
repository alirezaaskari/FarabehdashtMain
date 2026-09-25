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
 * مواجهه روزانه با ارتعاش تمام بدن، نرمال‌شده به هشت ساعت.
 *
 *     Aⱼ(8) = kⱼ × √( Σ a_wj,i² × Tᵢ / T₀ ) ، kₓ = k_y = 1.4 ، k_z = 1
 *     A(8)  = max(Aₓ(8), A_y(8), A_z(8))
 *
 * هر محور جدا حساب می‌شود و بزرگ‌ترین، مواجهه روزانه است؛ جمع برداری محورها
 * در این روش نیست.
 */
final readonly class WholeBodyVibrationV1 implements CrossValidated, Formula
{
    private const int MAX_OPERATIONS = 24;

    private const float REFERENCE_HOURS = 8.0;

    private const float HORIZONTAL_FACTOR = 1.4;

    public function id(): string
    {
        return 'whole-body-vibration';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function title(): string
    {
        return 'مواجهه روزانه با ارتعاش تمام بدن A(8)';
    }

    public function reference(): Reference
    {
        return new Reference(
            title: 'ISO 2631-1 — Mechanical vibration and shock: Evaluation of human exposure to whole-body vibration',
            publisher: 'سازمان بین‌المللی استانداردسازی (ISO)',
            year: 1997,
            relation: 'Aⱼ(8) = kⱼ·√(Σ a_wj,i² · Tᵢ / 8) ، A(8) = max(1.4·aₓ ، 1.4·a_y ، a_z)',
            note: 'شتاب وزن‌دار RMS هر محور (Wd برای x و y، Wk برای z) در وضعیت نشسته یا ایستاده.',
        );
    }

    public function limitations(): array
    {
        return [
            'وقتی ارتعاش ضربه‌دار است (ضریب قله بیش از ۹)، RMS مواجهه را کمتر از واقع نشان می‌دهد و مقدار دز ارتعاش (VDV) لازم است.',
            'ضریب‌های ۱٫۴ برای محور افقی مخصوص وضعیت نشسته و ایستاده است؛ برای وضعیت خوابیده کاربرد ندارد.',
            'مقایسه A(8) با مقدار اقدام یا حد مواجهه، تفسیر است و در خروجی ابزار انجام نمی‌شود.',
        ];
    }

    public function inputs(): array
    {
        $axis = static fn (string $key, string $label): InputDefinition => InputDefinition::list(
            $key, $label, Unit::MetrePerSecondSquared, 0.0, 50.0, 1, self::MAX_OPERATIONS,
        );

        return [
            'x_axis' => $axis('x_axis', 'شتاب وزن‌دار محور x'),
            'y_axis' => $axis('y_axis', 'شتاب وزن‌دار محور y'),
            'z_axis' => $axis('z_axis', 'شتاب وزن‌دار محور z'),
            'durations' => InputDefinition::list('durations', 'مدت روزانه هر کار', Unit::Hour, 0.0, 24.0, 1, self::MAX_OPERATIONS),
        ];
    }

    public function outputs(): array
    {
        return [
            'x_exposure' => Unit::MetrePerSecondSquared,
            'y_exposure' => Unit::MetrePerSecondSquared,
            'z_exposure' => Unit::MetrePerSecondSquared,
            'daily_exposure' => Unit::MetrePerSecondSquared,
            'covered_duration' => Unit::Hour,
        ];
    }

    public function crossCheck(InputSet $inputs): array
    {
        return Durations::check($inputs, ['x_axis', 'y_axis', 'z_axis'], 'قرائت‌های محور');
    }

    public function compute(InputSet $inputs): Outcome
    {
        $durations = $inputs->values('durations');

        $axis = function (string $key, float $factor) use ($inputs, $durations): float {
            $sum = 0.0;

            foreach ($inputs->values($key) as $index => $acceleration) {
                $sum += $acceleration ** 2 * $durations[$index];
            }

            return $factor * sqrt($sum / self::REFERENCE_HOURS);
        };

        $x = $axis('x_axis', self::HORIZONTAL_FACTOR);
        $y = $axis('y_axis', self::HORIZONTAL_FACTOR);
        $z = $axis('z_axis', 1.0);

        $dominant = match (max($x, $y, $z)) {
            $z => 'z',
            $x => 'x',
            default => 'y',
        };

        return new Outcome(
            values: [
                'x_exposure' => $x,
                'y_exposure' => $y,
                'z_exposure' => $z,
                'daily_exposure' => max($x, $y, $z),
                'covered_duration' => array_sum($durations),
            ],
            notes: [sprintf('محور غالب: %s. کنترل (صندلی، سرعت، سطح مسیر) را از همین محور شروع کنید.', $dominant)],
        );
    }
}
