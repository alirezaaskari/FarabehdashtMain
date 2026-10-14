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
 * ارزیابی سریع مواجهه (QEC)، برگه امتیاز نسخه نهایی (HSE RR211؛ David و همکاران ۲۰۰۸).
 *
 *     هر ناحیه = جمع جدول‌های دوتایی پاسخ مشاهده‌گر و کارگر
 *     کمر (ایستا: ۴ جدول، جابه‌جایی بار: ۵) · شانه و بازو ۵ · مچ و دست ۵ · گردن ۲
 *     E = (کمر + شانه + مچ + گردن) ÷ بیشینه × ۱۰۰ → سطح اقدام (Brown و Li ۲۰۰۳)
 *
 * پرسش B دو شاخه دارد و یک ورودی است: کد ۱ و ۲ کار ایستاده یا نشسته در یک جا
 * (کمر ثابت نیست / هست)، کد ۳ تا ۵ جابه‌جایی بار با بسامد حرکت کمر. شاخه، هم
 * جدول‌های کمر را عوض می‌کند و هم بیشینه E را (۱۶۲ ایستا، ۱۷۶ جابه‌جایی بار).
 */
final readonly class QecV1 implements CrossValidated, Formula
{
    /**
     * همه جدول‌های برگه امتیاز گوشه‌ای از همین یک جدول‌اند: [سطح عامل سطری][سطح عامل ستونی]،
     * سطر عاملی که تا ۴ سطح دارد (وزن) و ستون عاملی که تا ۳ سطح.
     *
     * @var list<list<int>>
     */
    private const array PAIR = [
        [2, 4, 6],
        [4, 6, 8],
        [6, 8, 10],
        [8, 10, 12],
    ];

    /**
     * جدول‌های هر ناحیه به ترتیب برگه: [نام ترکیب، عامل سطری، عامل ستونی].
     * «movement» سطح پرسش B در شاخه خودش است (۱..۲ یا ۱..۳).
     *
     * @var array<string, list<array{string, string, string}>>
     */
    private const array INTERACTIONS = [
        'back' => [
            ['پوسچر کمر و وزن بار', 'handled_weight', 'back_posture'],
            ['پوسچر کمر و مدت کار', 'task_duration', 'back_posture'],
            ['مدت کار و وزن بار', 'handled_weight', 'task_duration'],
        ],
        'back_static' => [
            ['کمر ثابت و مدت کار', 'task_duration', 'movement'],
        ],
        'back_moving' => [
            ['بسامد حرکت کمر و وزن بار', 'handled_weight', 'movement'],
            ['بسامد حرکت کمر و مدت کار', 'task_duration', 'movement'],
        ],
        'shoulder' => [
            ['ارتفاع دست و وزن بار', 'handled_weight', 'hand_height'],
            ['ارتفاع دست و مدت کار', 'task_duration', 'hand_height'],
            ['مدت کار و وزن بار', 'handled_weight', 'task_duration'],
            ['بسامد حرکت شانه و وزن بار', 'handled_weight', 'arm_movement'],
            ['بسامد حرکت شانه و مدت کار', 'task_duration', 'arm_movement'],
        ],
        'wrist' => [
            ['حرکت تکراری و نیروی دست', 'hand_force', 'hand_repetition'],
            ['حرکت تکراری و مدت کار', 'task_duration', 'hand_repetition'],
            ['مدت کار و نیروی دست', 'hand_force', 'task_duration'],
            ['پوسچر مچ و نیروی دست', 'hand_force', 'wrist_posture'],
            ['پوسچر مچ و مدت کار', 'task_duration', 'wrist_posture'],
        ],
        'neck' => [
            ['پوسچر گردن و مدت کار', 'task_duration', 'neck_posture'],
            ['نیاز بینایی و مدت کار', 'task_duration', 'visual_demand'],
        ],
    ];

    /** مرز بالای «کم»، «متوسط» و «زیاد» هر ناحیه؛ بالاتر «خیلی زیاد». */
    private const array BANDS = [
        'back_static' => [15, 22, 29],
        'back_moving' => [20, 30, 40],
        'shoulder' => [20, 30, 40],
        'wrist' => [20, 30, 40],
        'neck' => [6, 10, 14],
    ];

    private const array BAND_NAMES = ['کم', 'متوسط', 'زیاد', 'خیلی زیاد'];

    /** بیشینه مجموع چهار ناحیه (Brown و Li ۲۰۰۳). */
    private const int MAX_STATIC = 162;

    private const int MAX_MOVING = 176;

    /** امتیاز ترکیبی که برگه بالاترین سطح می‌داند و فوری باید به آن رسید. */
    private const int URGENT_INTERACTION = 10;

    private const array ACTION_LEVELS = [
        1 => 'شاخص مواجهه کمتر از ۴۰ درصد: پذیرفتنی.',
        2 => 'شاخص مواجهه ۴۰ تا ۴۹ درصد: بررسی بیشتر لازم است.',
        3 => 'شاخص مواجهه ۵۰ تا ۶۹ درصد: بررسی بیشتر و تغییر به‌زودی.',
        4 => 'شاخص مواجهه ۷۰ درصد یا بیشتر: بررسی و تغییر فوری.',
    ];

    /** عامل‌های تک‌پرسشی: خروجی → [ورودی، نام]. امتیاز هر سطح مجذور آن است (۱، ۴، ۹، ۱۶). */
    private const array FACTORS = [
        'qec_driving_score' => ['driving_time', 'رانندگی'],
        'qec_vibration_score' => ['vibration_time', 'ارتعاش'],
        'qec_pace_score' => ['work_pace', 'سرعت کار'],
        'qec_stress_score' => ['work_stress', 'استرس کار'],
    ];

    public function id(): string
    {
        return 'qec';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function title(): string
    {
        return 'ارزیابی سریع مواجهه QEC';
    }

    public function reference(): Reference
    {
        return new Reference(
            title: 'David G, Woods V, Li G, Buckle P. The development of the Quick Exposure Check (QEC) for assessing exposure to risk factors for work-related musculoskeletal disorders. Applied Ergonomics 2008;39(1):57–69',
            publisher: 'Applied Ergonomics (Elsevier)؛ راهنما و برگه امتیاز: HSE Research Report RR211',
            year: 2008,
            relation: 'ناحیه = Σ جدول‌های دوتایی پاسخ‌ها ؛ E = (کمر + شانه + مچ + گردن) / Xmax × 100 ، Xmax = 162 (ایستا) یا 176 (جابه‌جایی بار)',
            note: 'جدول‌های دوتایی، مرزهای سطح مواجهه هر ناحیه و امتیاز رانندگی، ارتعاش، سرعت کار و استرس از برگه امتیاز رسمی QEC در راهنمای RR211 گرفته شده‌اند. برگه برای «خیلی زیاد» کمر ایستا ۲۹ تا ۴۰ چاپ کرده که با «زیاد» هم‌پوشان است و بیشینه واقعی ۴۲ را نمی‌پوشاند؛ اینجا از ۳۰ به بالا گرفته شده است. شاخص E و سطح‌های اقدام از Brown R, Li G. The development of action levels for the Quick Exposure Check (QEC) system. Contemporary Ergonomics 2003 است.',
        );
    }

    public function limitations(): array
    {
        return [
            'QEC روش غربالگری مواجهه است: به گفته راهنمای آن، امتیازها رابطه‌ای فرضی میان مواجهه و پیامد سلامت را نشان می‌دهند، نه احتمال آسیب یا تشخیص.',
            'سطح‌های اقدام شاخص E به گفته Brown و Li «مقدماتی» و فقط راهنمای کلی‌اند؛ از ۴۰ درصد به بالا امتیاز تک‌تک ناحیه‌ها را هم ببینید.',
            'نیمی از پاسخ‌ها (وزن، مدت، نیرو، بینایی، رانندگی، ارتعاش، سرعت کار و استرس) از خود کارگر پرسیده می‌شود؛ در تردید، سطح بالاتر را بزنید.',
            'رانندگی، ارتعاش، سرعت کار و استرس جدا امتیاز می‌گیرند و در شاخص E نیستند.',
        ];
    }

    public function inputs(): array
    {
        $code = static fn (string $key, string $label, float $max): InputDefinition => InputDefinition::single($key, $label, Unit::Score, 1.0, $max);

        return [
            'back_posture' => $code('back_posture', 'پوسچر کمر', 3.0),
            'back_movement' => $code('back_movement', 'نوع کار و حرکت کمر', 5.0),
            'hand_height' => $code('hand_height', 'ارتفاع دست‌ها', 3.0),
            'arm_movement' => $code('arm_movement', 'حرکت شانه و بازو', 3.0),
            'wrist_posture' => $code('wrist_posture', 'پوسچر مچ', 2.0),
            'hand_repetition' => $code('hand_repetition', 'تکرار حرکت دست', 3.0),
            'neck_posture' => $code('neck_posture', 'خم شدن یا چرخش سر و گردن', 3.0),
            'handled_weight' => $code('handled_weight', 'بیشترین وزنی که با دست جابه‌جا می‌کند', 4.0),
            'task_duration' => $code('task_duration', 'مدت این کار در روز', 3.0),
            'hand_force' => $code('hand_force', 'بیشترین نیروی یک دست', 3.0),
            'visual_demand' => $code('visual_demand', 'نیاز بینایی', 2.0),
            'driving_time' => $code('driving_time', 'رانندگی در کار', 3.0),
            'vibration_time' => $code('vibration_time', 'کار با ابزار لرزان', 3.0),
            'work_pace' => $code('work_pace', 'سختی رسیدن به سرعت کار', 3.0),
            'work_stress' => $code('work_stress', 'استرس کار', 4.0),
        ];
    }

    public function outputs(): array
    {
        return [
            'qec_exposure' => Unit::Percent,
            'qec_action_level' => Unit::Score,
            'qec_total_score' => Unit::Score,
            'qec_back_score' => Unit::Score,
            'qec_shoulder_score' => Unit::Score,
            'qec_wrist_score' => Unit::Score,
            'qec_neck_score' => Unit::Score,
            'qec_driving_score' => Unit::Score,
            'qec_vibration_score' => Unit::Score,
            'qec_pace_score' => Unit::Score,
            'qec_stress_score' => Unit::Score,
        ];
    }

    public function crossCheck(InputSet $inputs): array
    {
        $errors = [];

        foreach ($this->inputs() as $key => $definition) {
            $value = $inputs->value($key);

            if (floor($value) !== $value) {
                $errors[] = new InputError($key, InputErrorCode::OutOfRange, sprintf('برای «%s» یکی از گزینه‌ها را انتخاب کنید.', $definition->label), ['given' => $value]);
            }
        }

        return $errors;
    }

    public function compute(InputSet $inputs): Outcome
    {
        $movement = (int) $inputs->value('back_movement');
        $static = $movement <= 2;
        $levels = static fn (string $factor): int => $factor === 'movement'
            ? ($static ? $movement : $movement - 2)
            : (int) $inputs->value($factor);

        $backBranch = $static ? 'back_static' : 'back_moving';
        $areas = [
            'back' => [...self::INTERACTIONS['back'], ...self::INTERACTIONS[$backBranch]],
            'shoulder' => self::INTERACTIONS['shoulder'],
            'wrist' => self::INTERACTIONS['wrist'],
            'neck' => self::INTERACTIONS['neck'],
        ];

        $scores = [];
        $urgent = [];

        foreach ($areas as $area => $interactions) {
            $scores[$area] = 0;

            foreach ($interactions as [$name, $row, $column]) {
                $cell = self::PAIR[$levels($row) - 1][$levels($column) - 1];
                $scores[$area] += $cell;

                if ($cell >= self::URGENT_INTERACTION) {
                    $urgent[$name] = $cell;
                }
            }
        }

        $total = array_sum($scores);
        $exposure = $total / ($static ? self::MAX_STATIC : self::MAX_MOVING) * 100;
        $actionLevel = match (true) {
            $exposure < 40 => 1,
            $exposure < 50 => 2,
            $exposure < 70 => 3,
            default => 4,
        };

        $factors = array_map(static fn (array $factor): int => ((int) $inputs->value($factor[0])) ** 2, self::FACTORS);

        return new Outcome(
            values: [
                'qec_exposure' => $exposure,
                'qec_action_level' => (float) $actionLevel,
                'qec_total_score' => (float) $total,
                'qec_back_score' => (float) $scores['back'],
                'qec_shoulder_score' => (float) $scores['shoulder'],
                'qec_wrist_score' => (float) $scores['wrist'],
                'qec_neck_score' => (float) $scores['neck'],
                ...array_map(floatval(...), $factors),
            ],
            notes: self::notes($actionLevel, $scores, $backBranch, $urgent, $factors),
        );
    }

    /**
     * @param  array<string, int>  $scores
     * @param  array<string, int>  $urgent
     * @param  array<string, int>  $factors
     * @return list<string>
     */
    private static function notes(int $actionLevel, array $scores, string $backBranch, array $urgent, array $factors): array
    {
        $names = ['back' => 'کمر', 'shoulder' => 'شانه و بازو', 'wrist' => 'مچ و دست', 'neck' => 'گردن'];
        $bands = [];

        foreach ($scores as $area => $score) {
            $limits = self::BANDS[$area === 'back' ? $backBranch : $area];
            $band = count(array_filter($limits, static fn (int $limit): bool => $score > $limit));
            $bands[] = sprintf('%s %s (%s)', $names[$area], self::BAND_NAMES[$band], SegmentLeaders::persian($score));
        }

        $notes = [
            self::ACTION_LEVELS[$actionLevel],
            sprintf('سطح مواجهه هر ناحیه: %s.', SegmentLeaders::join($bands)),
        ];

        if ($urgent !== []) {
            $listed = array_map(static fn (string $name, int $cell): string => sprintf('%s (%s)', $name, SegmentLeaders::persian($cell)), array_keys($urgent), $urgent);
            $notes[] = sprintf('این ترکیب‌ها در بالاترین سطح برگه‌اند و باید زودتر از بقیه اصلاح شوند: %s.', SegmentLeaders::join($listed));
        }

        $raised = [];

        foreach (self::FACTORS as $key => [, $name]) {
            if ($factors[$key] > 1) {
                $raised[] = sprintf('%s (%s)', $name, SegmentLeaders::persian($factors[$key]));
            }
        }

        if ($raised !== []) {
            $notes[] = sprintf('این عامل‌ها از سطح کم بالاترند و مواجهه با آن‌ها باید کم شود: %s.', SegmentLeaders::join($raised));
        }

        return $notes;
    }
}
