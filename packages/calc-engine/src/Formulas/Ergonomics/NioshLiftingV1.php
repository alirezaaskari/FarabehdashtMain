<?php

declare(strict_types=1);

namespace Farabehdasht\CalcEngine\Formulas\Ergonomics;

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
 * معادله بلندکردن بار NIOSH (بازنگری ۱۹۹۱)، واحد متریک.
 *
 *     RWL = LC × HM × VM × DM × AM × FM × CM ، LC = 23 kg
 *     HM = 25/H ، VM = 1 − 0.003·|V − 75| ، DM = 0.82 + 4.5/D ، AM = 1 − 0.0032·A
 *     LI = وزن بار / RWL
 *
 * FM و CM از جدول‌های راهنمای کاربرد NIOSH می‌آیند. بسامد میان دو ردیف جدول
 * خطی درون‌یابی می‌شود. مدت کار و نوع دستگیره کد عددی‌اند، چون موتور فقط
 * عدد می‌پذیرد؛ لایه نمایش برایشان فهرست انتخابی می‌گذارد.
 */
final readonly class NioshLiftingV1 implements CrossValidated, Formula
{
    private const float LOAD_CONSTANT = 23.0;

    /** مرز ارتفاع عمودی در جدول‌های FM و CM (سانتی‌متر). */
    private const float KNUCKLE_HEIGHT = 75.0;

    /**
     * جدول ۵ راهنمای کاربرد: بسامد (بار در دقیقه) → [≤۱ ساعت، ≤۲ ساعت، ≤۸ ساعت]،
     * هرکدام [V < 75 ، V ≥ 75].
     *
     * @var list<array{float, array{array{float, float}, array{float, float}, array{float, float}}}>
     */
    private const array FREQUENCY_TABLE = [
        [0.2, [[1.00, 1.00], [0.95, 0.95], [0.85, 0.85]]],
        [0.5, [[0.97, 0.97], [0.92, 0.92], [0.81, 0.81]]],
        [1.0, [[0.94, 0.94], [0.88, 0.88], [0.75, 0.75]]],
        [2.0, [[0.91, 0.91], [0.84, 0.84], [0.65, 0.65]]],
        [3.0, [[0.88, 0.88], [0.79, 0.79], [0.55, 0.55]]],
        [4.0, [[0.84, 0.84], [0.72, 0.72], [0.45, 0.45]]],
        [5.0, [[0.80, 0.80], [0.60, 0.60], [0.35, 0.35]]],
        [6.0, [[0.75, 0.75], [0.50, 0.50], [0.27, 0.27]]],
        [7.0, [[0.70, 0.70], [0.42, 0.42], [0.22, 0.22]]],
        [8.0, [[0.60, 0.60], [0.35, 0.35], [0.18, 0.18]]],
        [9.0, [[0.52, 0.52], [0.30, 0.30], [0.00, 0.15]]],
        [10.0, [[0.45, 0.45], [0.26, 0.26], [0.00, 0.13]]],
        [11.0, [[0.41, 0.41], [0.00, 0.23], [0.00, 0.00]]],
        [12.0, [[0.37, 0.37], [0.00, 0.21], [0.00, 0.00]]],
        [13.0, [[0.00, 0.34], [0.00, 0.00], [0.00, 0.00]]],
        [14.0, [[0.00, 0.31], [0.00, 0.00], [0.00, 0.00]]],
        [15.0, [[0.00, 0.28], [0.00, 0.00], [0.00, 0.00]]],
    ];

    /** کد مدت کار → ستون جدول FM. */
    private const array DURATIONS = [1 => 0, 2 => 1, 8 => 2];

    /** کد دستگیره → [V < 75 ، V ≥ 75]. */
    private const array COUPLINGS = [
        1 => [1.00, 1.00],
        2 => [0.95, 1.00],
        3 => [0.90, 0.90],
    ];

    public function id(): string
    {
        return 'niosh-lifting';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function title(): string
    {
        return 'معادله بلندکردن بار NIOSH';
    }

    public function reference(): Reference
    {
        return new Reference(
            title: 'Applications Manual for the Revised NIOSH Lifting Equation (DHHS/NIOSH Publication 94-110)',
            publisher: 'مؤسسه ملی ایمنی و بهداشت شغلی آمریکا (NIOSH)',
            year: 1994,
            relation: 'RWL = 23 × (25/H) × (1−0.003|V−75|) × (0.82+4.5/D) × (1−0.0032A) × FM × CM ، LI = L/RWL',
            note: 'FM از جدول ۵ و CM از جدول ۷ همان راهنما؛ بسامد میان ردیف‌ها خطی درون‌یابی می‌شود.',
        );
    }

    public function limitations(): array
    {
        return [
            'معادله فقط برای بلندکردن و پایین‌گذاشتن دوبدستی در حالت ایستاده است؛ حمل، هل‌دادن، کشیدن، کار یک‌دستی و نشسته را پوشش نمی‌دهد.',
            'جابه‌جایی ناگهانی، سطح لغزنده، دمای نامناسب و بار ناپایدار در معادله نیستند.',
            'کد مدت کار زمان بازیابی میان دوره‌ها را هم فرض می‌کند (راهنمای NIOSH)؛ انتخاب کد درست قضاوت کارشناس است.',
            'شاخص بلندکردن بیش از یک، نشانه افزایش خطر برای بخشی از کارکنان است، نه تشخیص آسیب یا حکم ممنوعیت.',
        ];
    }

    public function inputs(): array
    {
        return [
            'load' => InputDefinition::single('load', 'وزن بار', Unit::Kilogram, 0.0, 100.0),
            'horizontal' => InputDefinition::single('horizontal', 'فاصله افقی (H)', Unit::Centimetre, 0.0, 63.0),
            'vertical' => InputDefinition::single('vertical', 'ارتفاع شروع از زمین (V)', Unit::Centimetre, 0.0, 175.0),
            'travel' => InputDefinition::single('travel', 'جابه‌جایی عمودی (D)', Unit::Centimetre, 0.0, 175.0),
            'asymmetry' => InputDefinition::single('asymmetry', 'زاویه چرخش تنه (A)', Unit::Degree, 0.0, 135.0),
            'frequency' => InputDefinition::single('frequency', 'بسامد بلندکردن', Unit::PerMinute, 0.0, 15.0),
            'duration' => InputDefinition::single('duration', 'مدت پیوسته کار', Unit::Hour, 1.0, 8.0),
            'coupling' => InputDefinition::single('coupling', 'کیفیت دستگیره', Unit::Ratio, 1.0, 3.0),
        ];
    }

    public function outputs(): array
    {
        return [
            'recommended_weight' => Unit::Kilogram,
            'lifting_index' => Unit::Ratio,
            'horizontal_multiplier' => Unit::Ratio,
            'vertical_multiplier' => Unit::Ratio,
            'distance_multiplier' => Unit::Ratio,
            'asymmetric_multiplier' => Unit::Ratio,
            'frequency_multiplier' => Unit::Ratio,
            'coupling_multiplier' => Unit::Ratio,
        ];
    }

    public function crossCheck(InputSet $inputs): array
    {
        $errors = [];

        if (! array_key_exists((int) $inputs->value('duration'), self::DURATIONS)
            || floor($inputs->value('duration')) !== $inputs->value('duration')) {
            $errors[] = new InputError('duration', InputErrorCode::OutOfRange, 'مدت کار یکی از سه گروه ۱، ۲ یا ۸ ساعت است.', ['given' => $inputs->value('duration')]);
        }

        if (floor($inputs->value('coupling')) !== $inputs->value('coupling')) {
            $errors[] = new InputError('coupling', InputErrorCode::OutOfRange, 'کیفیت دستگیره یکی از سه گروه خوب (۱)، متوسط (۲) یا ضعیف (۳) است.', ['given' => $inputs->value('coupling')]);
        }

        if ($errors === [] && $this->frequencyMultiplier($inputs) <= 0.0) {
            $errors[] = new InputError(
                'frequency',
                InputErrorCode::OutOfRange,
                'با این بسامد و مدت کار، ضریب بسامد در جدول NIOSH صفر است؛ کار بیرون از دامنه معادله است و باید بازطراحی شود.',
                ['given' => $inputs->value('frequency')],
            );
        }

        return $errors;
    }

    public function compute(InputSet $inputs): Outcome
    {
        $vertical = $inputs->value('vertical');

        $multipliers = [
            'horizontal_multiplier' => 25.0 / max(25.0, $inputs->value('horizontal')),
            'vertical_multiplier' => 1.0 - 0.003 * abs($vertical - self::KNUCKLE_HEIGHT),
            'distance_multiplier' => 0.82 + 4.5 / max(25.0, $inputs->value('travel')),
            'asymmetric_multiplier' => 1.0 - 0.0032 * $inputs->value('asymmetry'),
            'frequency_multiplier' => $this->frequencyMultiplier($inputs),
            'coupling_multiplier' => self::COUPLINGS[(int) $inputs->value('coupling')][$vertical < self::KNUCKLE_HEIGHT ? 0 : 1],
        ];

        $recommended = self::LOAD_CONSTANT * array_product($multipliers);

        $notes = [];

        if ($inputs->value('horizontal') < 25.0 || $inputs->value('travel') < 25.0) {
            $notes[] = 'فاصله افقی یا جابه‌جایی عمودی کمتر از ۲۵ سانتی‌متر، طبق راهنمای NIOSH همان ۲۵ گرفته شد.';
        }

        return new Outcome(
            values: [
                'recommended_weight' => $recommended,
                'lifting_index' => $inputs->value('load') / $recommended,
                ...$multipliers,
            ],
            notes: $notes,
        );
    }

    private function frequencyMultiplier(InputSet $inputs): float
    {
        $column = self::DURATIONS[(int) $inputs->value('duration')];
        $side = $inputs->value('vertical') < self::KNUCKLE_HEIGHT ? 0 : 1;
        $frequency = max(0.2, $inputs->value('frequency'));

        $previous = null;

        foreach (self::FREQUENCY_TABLE as [$rate, $row]) {
            $value = $row[$column][$side];

            if ($frequency <= $rate) {
                if ($previous === null || $frequency === $rate) {
                    return $value;
                }

                [$lowRate, $lowValue] = $previous;

                return $lowValue + ($value - $lowValue) * ($frequency - $lowRate) / ($rate - $lowRate);
            }

            $previous = [$rate, $value];
        }

        return 0.0;
    }
}
