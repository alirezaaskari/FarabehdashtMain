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
 * ارزیابی سریع کل بدن (REBA)، هیگنت و مک‌آتامنی ۲۰۰۰.
 *
 *     تنه، گردن، پا        → جدول A → + بار          = امتیاز A
 *     بازو، ساعد، مچ       → جدول B → + دستگیره      = امتیاز B
 *     جدول C(A, B) + فعالیت = امتیاز نهایی ۱ تا ۱۵ → سطح ریسک و اقدام ۰ تا ۴
 *
 * مثل RULA همه ورودی‌ها کد عددی‌اند و هر اجرا یک سمت بدن است. تعدیل‌ها همان
 * مقاله‌اند: چرخش یا خم شدن به پهلو برای گردن و تنه هر کدام یک امتیاز
 * (نه دو)، چون جدول A برای گردن بیش از ۳ و تنه بیش از ۵ خانه ندارد.
 */
final readonly class RebaV1 implements CrossValidated, Formula
{
    /**
     * جدول A مقاله: [تنه ۱..۵][گردن ۱..۳][پا ۱..۴].
     *
     * @var list<list<list<int>>>
     */
    private const array TABLE_A = [
        [[1, 2, 3, 4], [1, 2, 3, 4], [3, 3, 5, 6]],
        [[2, 3, 4, 5], [3, 4, 5, 6], [4, 5, 6, 7]],
        [[2, 4, 5, 6], [4, 5, 6, 7], [5, 6, 7, 8]],
        [[3, 5, 6, 7], [5, 6, 7, 8], [6, 7, 8, 9]],
        [[4, 6, 7, 8], [6, 7, 8, 9], [7, 8, 9, 9]],
    ];

    /**
     * جدول B مقاله: [بازو ۱..۶][ساعد ۱..۲][مچ ۱..۳].
     *
     * @var list<list<list<int>>>
     */
    private const array TABLE_B = [
        [[1, 2, 2], [1, 2, 3]],
        [[1, 2, 3], [2, 3, 4]],
        [[3, 4, 5], [4, 5, 5]],
        [[4, 5, 5], [5, 6, 7]],
        [[6, 7, 8], [7, 8, 8]],
        [[7, 8, 8], [8, 9, 9]],
    ];

    /**
     * جدول C مقاله: [امتیاز A ۱..۱۲][امتیاز B ۱..۱۲].
     *
     * @var list<list<int>>
     */
    private const array TABLE_C = [
        [1, 1, 1, 2, 3, 3, 4, 5, 6, 7, 7, 7],
        [1, 2, 2, 3, 4, 4, 5, 6, 6, 7, 7, 8],
        [2, 3, 3, 3, 4, 5, 6, 7, 7, 8, 8, 8],
        [3, 4, 4, 4, 5, 6, 7, 8, 8, 9, 9, 9],
        [4, 4, 4, 5, 6, 7, 8, 8, 9, 9, 9, 9],
        [6, 6, 6, 7, 8, 8, 9, 9, 10, 10, 10, 10],
        [7, 7, 7, 8, 9, 9, 9, 10, 10, 11, 11, 11],
        [8, 8, 8, 9, 10, 10, 10, 10, 10, 11, 11, 11],
        [9, 9, 9, 10, 10, 10, 11, 11, 11, 12, 12, 12],
        [10, 10, 10, 11, 11, 11, 11, 12, 12, 12, 12, 12],
        [11, 11, 11, 11, 12, 12, 12, 12, 12, 12, 12, 12],
        [12, 12, 12, 12, 12, 12, 12, 12, 12, 12, 12, 12],
    ];

    /** سطح ریسک و اقدام هر بازه امتیاز، به متن مقاله. */
    private const array ACTION_LEVELS = [
        0 => 'سطح اقدام ۰، ریسک ناچیز: اقدامی لازم نیست.',
        1 => 'سطح اقدام ۱، ریسک کم: ممکن است اقدام لازم باشد.',
        2 => 'سطح اقدام ۲، ریسک متوسط: اقدام لازم است.',
        3 => 'سطح اقدام ۳، ریسک زیاد: اقدام باید به‌زودی انجام شود.',
        4 => 'سطح اقدام ۴، ریسک بسیار زیاد: اقدام باید همین حالا انجام شود.',
    ];

    /** عضوهایی که برای «بیشترین سهم» مقایسه می‌شوند: خروجی → [نام، بیشینه امتیاز]. */
    private const array SEGMENTS = [
        'trunk_score' => ['تنه', 5],
        'neck_score' => ['گردن', 3],
        'legs_score' => ['پاها', 4],
        'upper_arm_score' => ['بازو', 6],
        'lower_arm_score' => ['ساعد', 2],
        'wrist_score' => ['مچ', 3],
    ];

    public function id(): string
    {
        return 'reba';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function title(): string
    {
        return 'ارزیابی پوسچر به روش REBA';
    }

    public function reference(): Reference
    {
        return new Reference(
            title: 'Hignett S, McAtamney L. Rapid Entire Body Assessment (REBA). Applied Ergonomics 2000;31(2):201–205',
            publisher: 'Applied Ergonomics (Elsevier)',
            year: 2000,
            relation: 'A = TableA(T, N, L) + load ; B = TableB(UA, LA, W) + coupling ; REBA = TableC(A, B) + activity',
            note: 'جدول‌های A، B و C و سطح‌های ریسک و اقدام عیناً از همان مقاله‌اند و با برگه REBA دانشگاه Cornell (Hedge) خانه به خانه مقایسه شده‌اند. مثال حل‌شده راهنمای Ergo-Plus مورد مرجع مستقل است.',
        );
    }

    public function limitations(): array
    {
        return [
            'REBA روش غربالگری است: امتیاز اولویت اقدام را نشان می‌دهد، نه احتمال آسیب یا تشخیص.',
            'هر اجرا یک سمت بدن و یک لحظه از کار است؛ بدترین پوسچر یا پوسچری که بیشترین زمان را دارد انتخاب کنید و سمت دیگر را جدا ارزیابی کنید.',
            'مدت کل کار در روز، استراحت، ارتعاش، سرما و فشار تماسی در امتیاز نیستند.',
            'بازه زاویه با چشم یا از روی عکس تخمین زده می‌شود و دو ارزیاب ممکن است در مرز بازه‌ها متفاوت قضاوت کنند.',
        ];
    }

    public function inputs(): array
    {
        $code = static fn (string $key, string $label, float $max, float $min = 1.0): InputDefinition => InputDefinition::single($key, $label, Unit::Score, $min, $max);
        $flag = static fn (string $key, string $label): InputDefinition => InputDefinition::single($key, $label, Unit::Score, 0.0, 1.0);

        return [
            'trunk' => $code('trunk', 'زاویه تنه', 4.0),
            'trunk_twisted_or_bent' => $flag('trunk_twisted_or_bent', 'تنه چرخیده یا به پهلو خم شده'),
            'neck' => $code('neck', 'زاویه گردن', 2.0),
            'neck_twisted_or_bent' => $flag('neck_twisted_or_bent', 'گردن چرخیده یا به پهلو خم شده'),
            'legs' => $code('legs', 'وضعیت پاها', 2.0),
            'knees' => $code('knees', 'خم شدن زانو', 2.0, 0.0),
            'load_class' => $code('load_class', 'بار یا نیرو', 2.0, 0.0),
            'load_shock' => $flag('load_shock', 'ضربه یا نیروی ناگهانی'),
            'upper_arm' => $code('upper_arm', 'زاویه بازو', 4.0),
            'shoulder_raised' => $flag('shoulder_raised', 'شانه بالا رفته'),
            'arm_abducted_or_rotated' => $flag('arm_abducted_or_rotated', 'بازو از بدن دور شده یا چرخیده'),
            'arm_supported' => $flag('arm_supported', 'بازو تکیه‌گاه دارد یا فرد به جلو تکیه داده'),
            'lower_arm' => $code('lower_arm', 'زاویه ساعد', 2.0),
            'wrist' => $code('wrist', 'زاویه مچ', 2.0),
            'wrist_twisted_or_bent' => $flag('wrist_twisted_or_bent', 'مچ به پهلو خم شده یا چرخیده'),
            'grip' => $code('grip', 'دستگیره و نحوه گرفتن', 3.0, 0.0),
            'static_posture' => $flag('static_posture', 'یک یا چند عضو بیش از یک دقیقه ثابت'),
            'repeated_action' => $flag('repeated_action', 'حرکت کوچک تکراری، بیش از ۴ بار در دقیقه'),
            'rapid_change' => $flag('rapid_change', 'تغییر سریع و بزرگ پوسچر یا تکیه‌گاه ناپایدار'),
        ];
    }

    public function outputs(): array
    {
        return [
            'reba_score' => Unit::Score,
            'action_level' => Unit::Score,
            'score_a' => Unit::Score,
            'score_b' => Unit::Score,
            'score_c' => Unit::Score,
            'activity_score' => Unit::Score,
            'posture_a' => Unit::Score,
            'posture_b' => Unit::Score,
            'trunk_score' => Unit::Score,
            'neck_score' => Unit::Score,
            'legs_score' => Unit::Score,
            'upper_arm_score' => Unit::Score,
            'lower_arm_score' => Unit::Score,
            'wrist_score' => Unit::Score,
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
        $code = static fn (string $key): int => (int) $inputs->value($key);

        $rawUpperArm = $code('upper_arm') + $code('shoulder_raised') + $code('arm_abducted_or_rotated') - $code('arm_supported');
        $segments = [
            'trunk_score' => $code('trunk') + $code('trunk_twisted_or_bent'),
            'neck_score' => $code('neck') + $code('neck_twisted_or_bent'),
            'legs_score' => $code('legs') + $code('knees'),
            'upper_arm_score' => max(1, $rawUpperArm),
            'lower_arm_score' => $code('lower_arm'),
            'wrist_score' => $code('wrist') + $code('wrist_twisted_or_bent'),
        ];

        $postureA = self::TABLE_A[$segments['trunk_score'] - 1][$segments['neck_score'] - 1][$segments['legs_score'] - 1];
        $postureB = self::TABLE_B[$segments['upper_arm_score'] - 1][$segments['lower_arm_score'] - 1][$segments['wrist_score'] - 1];

        $scoreA = $postureA + $code('load_class') + $code('load_shock');
        $scoreB = $postureB + $code('grip');
        $scoreC = self::TABLE_C[$scoreA - 1][$scoreB - 1];
        $activity = $code('static_posture') + $code('repeated_action') + $code('rapid_change');

        $score = $scoreC + $activity;
        $actionLevel = match (true) {
            $score === 1 => 0,
            $score <= 3 => 1,
            $score <= 7 => 2,
            $score <= 10 => 3,
            default => 4,
        };

        $notes = [self::ACTION_LEVELS[$actionLevel]];

        if ($rawUpperArm < 1) {
            $notes[] = 'امتیاز بازو با کم‌کردن تکیه‌گاه به کمتر از ۱ می‌رسید؛ کمترین امتیاز جدول B یعنی ۱ گرفته شد.';
        }

        $leaders = SegmentLeaders::describe($segments, self::SEGMENTS);

        if ($leaders !== '') {
            $notes[] = sprintf('بیشترین سهم در امتیاز پوسچر را %s دارد؛ اصلاح از همین‌جا بیشترین اثر را دارد.', $leaders);
        }

        return new Outcome(
            values: [
                'reba_score' => (float) $score,
                'action_level' => (float) $actionLevel,
                'score_a' => (float) $scoreA,
                'score_b' => (float) $scoreB,
                'score_c' => (float) $scoreC,
                'activity_score' => (float) $activity,
                'posture_a' => (float) $postureA,
                'posture_b' => (float) $postureB,
                ...array_map(floatval(...), $segments),
            ],
            notes: $notes,
        );
    }
}
