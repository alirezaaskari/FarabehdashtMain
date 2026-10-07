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
 * ارزیابی سریع اندام فوقانی (RULA)، مک‌آتامنی و کورلت ۱۹۹۳.
 *
 *     بازو، ساعد، مچ، چرخش مچ  → جدول A → + عضله + نیرو = C
 *     گردن، تنه، پا           → جدول B → + عضله + نیرو = D
 *     جدول C(C, D) = امتیاز نهایی ۱ تا ۷ → سطح اقدام ۱ تا ۴
 *
 * همه ورودی‌ها کد عددی‌اند: بازه زاویه هر عضو یک کد، و هر تعدیل (شانه بالا،
 * چرخش گردن، …) صفر یا یک. لایه نمایش برایشان گزینه و تیک می‌گذارد.
 * یک سمت بدن در هر اجرا؛ روش اصلی دو سمت را جدا ارزیابی می‌کند.
 */
final readonly class RulaV1 implements CrossValidated, Formula
{
    /**
     * جدول A مقاله: [بازو ۱..۶][ساعد ۱..۳] → هشت ستون (مچ ۱..۴ × چرخش ۱..۲).
     *
     * @var list<list<list<int>>>
     */
    private const array TABLE_A = [
        [[1, 2, 2, 2, 2, 3, 3, 3], [2, 2, 2, 2, 3, 3, 3, 3], [2, 3, 3, 3, 3, 3, 4, 4]],
        [[2, 3, 3, 3, 3, 4, 4, 4], [3, 3, 3, 3, 3, 4, 4, 4], [3, 4, 4, 4, 4, 4, 5, 5]],
        [[3, 3, 4, 4, 4, 4, 5, 5], [3, 4, 4, 4, 4, 4, 5, 5], [4, 4, 4, 4, 4, 5, 5, 5]],
        [[4, 4, 4, 4, 4, 5, 5, 5], [4, 4, 4, 4, 4, 5, 5, 5], [4, 4, 4, 5, 5, 5, 6, 6]],
        [[5, 5, 5, 5, 5, 6, 6, 7], [5, 6, 6, 6, 6, 7, 7, 7], [6, 6, 6, 7, 7, 7, 7, 8]],
        [[7, 7, 7, 7, 7, 8, 8, 9], [8, 8, 8, 8, 8, 9, 9, 9], [9, 9, 9, 9, 9, 9, 9, 9]],
    ];

    /**
     * جدول B مقاله: [گردن ۱..۶] → دوازده ستون (تنه ۱..۶ × پا ۱..۲).
     *
     * @var list<list<int>>
     */
    private const array TABLE_B = [
        [1, 3, 2, 3, 3, 4, 5, 5, 6, 6, 7, 7],
        [2, 3, 2, 3, 4, 5, 5, 5, 6, 7, 7, 7],
        [3, 3, 3, 4, 4, 5, 5, 6, 6, 7, 7, 7],
        [5, 5, 5, 6, 6, 7, 7, 7, 7, 7, 8, 8],
        [7, 7, 7, 7, 7, 8, 8, 8, 8, 8, 8, 8],
        [8, 8, 8, 8, 8, 8, 8, 9, 9, 9, 9, 9],
    ];

    /**
     * جدول C مقاله: [C ۱..۸+][D ۱..۷+].
     *
     * @var list<list<int>>
     */
    private const array TABLE_C = [
        [1, 2, 3, 3, 4, 5, 5],
        [2, 2, 3, 4, 4, 5, 5],
        [3, 3, 3, 4, 4, 5, 6],
        [3, 3, 3, 4, 5, 6, 6],
        [4, 4, 4, 5, 6, 7, 7],
        [4, 4, 5, 6, 6, 7, 7],
        [5, 5, 6, 6, 7, 7, 7],
        [5, 5, 6, 7, 7, 7, 7],
    ];

    /** سطح اقدام هر امتیاز نهایی، به متن مقاله. */
    private const array ACTION_LEVELS = [
        1 => 'سطح اقدام ۱: پوسچر پذیرفتنی است، به شرطی که مدت طولانی حفظ یا تکرار نشود.',
        2 => 'سطح اقدام ۲: بررسی بیشتر لازم است و ممکن است تغییر نیاز باشد.',
        3 => 'سطح اقدام ۳: بررسی و تغییر باید به‌زودی انجام شود.',
        4 => 'سطح اقدام ۴: بررسی و تغییر باید فوراً انجام شود.',
    ];

    /** عضوهایی که برای «بیشترین سهم» مقایسه می‌شوند: خروجی → [نام، بیشینه امتیاز]. */
    private const array SEGMENTS = [
        'upper_arm_score' => ['بازو', 6],
        'lower_arm_score' => ['ساعد', 3],
        'wrist_score' => ['مچ', 4],
        'neck_score' => ['گردن', 6],
        'trunk_score' => ['تنه', 6],
    ];

    public function id(): string
    {
        return 'rula';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function title(): string
    {
        return 'ارزیابی پوسچر به روش RULA';
    }

    public function reference(): Reference
    {
        return new Reference(
            title: 'McAtamney L, Corlett EN. RULA: a survey method for the investigation of work-related upper limb disorders. Applied Ergonomics 1993;24(2):91–99',
            publisher: 'Applied Ergonomics (Elsevier)',
            year: 1993,
            relation: 'C = TableA(UA, LA, W, WT) + muscle + force ; D = TableB(N, T, L) + muscle + force ; RULA = TableC(C, D)',
            note: 'جدول‌های A، B و C و سطح‌های اقدام عیناً از همان مقاله‌اند. مثال‌های حل‌شده راهنمای آموزشی Ergo-Plus (RULA Training Companion Guide) مورد مرجع مستقل‌اند.',
        );
    }

    public function limitations(): array
    {
        return [
            'RULA روش غربالگری است: امتیاز اولویت اقدام را نشان می‌دهد، نه احتمال آسیب یا تشخیص.',
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
            'upper_arm' => $code('upper_arm', 'زاویه بازو', 4.0),
            'shoulder_raised' => $flag('shoulder_raised', 'شانه بالا رفته'),
            'arm_abducted' => $flag('arm_abducted', 'بازو از بدن دور شده'),
            'arm_supported' => $flag('arm_supported', 'بازو تکیه‌گاه دارد یا فرد به جلو تکیه داده'),
            'lower_arm' => $code('lower_arm', 'زاویه ساعد', 2.0),
            'lower_arm_out' => $flag('lower_arm_out', 'ساعد از خط وسط بدن رد شده یا به کنار بدن رفته'),
            'wrist' => $code('wrist', 'زاویه مچ', 3.0),
            'wrist_bent' => $flag('wrist_bent', 'مچ به سمت انگشت شست یا کوچک خم شده'),
            'wrist_twist' => $code('wrist_twist', 'چرخش مچ', 2.0),
            'arm_muscle' => $flag('arm_muscle', 'کار دست ایستا یا تکراری'),
            'arm_force' => $code('arm_force', 'نیرو یا بار دست', 3.0, 0.0),
            'neck' => $code('neck', 'زاویه گردن', 4.0),
            'neck_twisted' => $flag('neck_twisted', 'گردن چرخیده'),
            'neck_side_bent' => $flag('neck_side_bent', 'گردن به پهلو خم شده'),
            'trunk' => $code('trunk', 'زاویه تنه', 4.0),
            'trunk_twisted' => $flag('trunk_twisted', 'تنه چرخیده'),
            'trunk_side_bent' => $flag('trunk_side_bent', 'تنه به پهلو خم شده'),
            'legs' => $code('legs', 'وضعیت پاها', 2.0),
            'body_muscle' => $flag('body_muscle', 'کار تنه و پا ایستا یا تکراری'),
            'body_force' => $code('body_force', 'نیرو یا بار روی تنه و پا', 3.0, 0.0),
        ];
    }

    public function outputs(): array
    {
        return [
            'rula_score' => Unit::Score,
            'action_level' => Unit::Score,
            'arm_wrist_score' => Unit::Score,
            'neck_trunk_leg_score' => Unit::Score,
            'posture_a' => Unit::Score,
            'posture_b' => Unit::Score,
            'upper_arm_score' => Unit::Score,
            'lower_arm_score' => Unit::Score,
            'wrist_score' => Unit::Score,
            'neck_score' => Unit::Score,
            'trunk_score' => Unit::Score,
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

        $rawUpperArm = $code('upper_arm') + $code('shoulder_raised') + $code('arm_abducted') - $code('arm_supported');
        $segments = [
            'upper_arm_score' => max(1, $rawUpperArm),
            'lower_arm_score' => $code('lower_arm') + $code('lower_arm_out'),
            'wrist_score' => $code('wrist') + $code('wrist_bent'),
            'neck_score' => $code('neck') + $code('neck_twisted') + $code('neck_side_bent'),
            'trunk_score' => $code('trunk') + $code('trunk_twisted') + $code('trunk_side_bent'),
        ];

        $postureA = self::TABLE_A[$segments['upper_arm_score'] - 1][$segments['lower_arm_score'] - 1][($segments['wrist_score'] - 1) * 2 + $code('wrist_twist') - 1];
        $postureB = self::TABLE_B[$segments['neck_score'] - 1][($segments['trunk_score'] - 1) * 2 + $code('legs') - 1];

        $armWrist = $postureA + $code('arm_muscle') + $code('arm_force');
        $neckTrunkLeg = $postureB + $code('body_muscle') + $code('body_force');

        $score = self::TABLE_C[min($armWrist, 8) - 1][min($neckTrunkLeg, 7) - 1];
        $actionLevel = intdiv($score + 1, 2);

        $notes = [self::ACTION_LEVELS[$actionLevel]];

        if ($rawUpperArm < 1) {
            $notes[] = 'امتیاز بازو با کم‌کردن تکیه‌گاه به کمتر از ۱ می‌رسید؛ کمترین امتیاز جدول A یعنی ۱ گرفته شد.';
        }

        $leaders = SegmentLeaders::describe($segments, self::SEGMENTS);

        if ($leaders !== '') {
            $notes[] = sprintf('بیشترین سهم در امتیاز پوسچر را %s دارد؛ اصلاح از همین‌جا بیشترین اثر را دارد.', $leaders);
        }

        return new Outcome(
            values: [
                'rula_score' => (float) $score,
                'action_level' => (float) $actionLevel,
                'arm_wrist_score' => (float) $armWrist,
                'neck_trunk_leg_score' => (float) $neckTrunkLeg,
                'posture_a' => (float) $postureA,
                'posture_b' => (float) $postureB,
                ...array_map(floatval(...), $segments),
            ],
            notes: $notes,
        );
    }
}
