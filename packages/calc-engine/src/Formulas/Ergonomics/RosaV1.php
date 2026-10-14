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
 * ارزیابی سریع فشار کار اداری (ROSA)، سان، ویلالتا و اندروز ۲۰۱۲.
 *
 *     ارتفاع + عمق نشیمن، دسته + پشتی → نمودار A + مدت نشستن = صندلی
 *     مانیتور + مدت، تلفن + مدت          → نمودار B
 *     صفحه‌کلید + مدت، ماوس + مدت         → نمودار C
 *     ROSA = بیشینه(صندلی، بیشینه(B، C))   → ۱ تا ۱۰، از ۵ به بالا سطح اقدام
 *
 * ورودی‌های گزینه‌ای کد گزینه‌اند، نه امتیاز: «خیلی پایین» و «خیلی بالا» هر
 * دو ۲ امتیاز دارند ولی گزارش باید بگوید کدام بوده. مدت هر بخش هم کد ۱ تا ۳
 * است که به ۱−، ۰ و ۱+ می‌رسد.
 */
final readonly class RosaV1 implements CrossValidated, Formula
{
    /**
     * نمودار A برگه رسمی: [ارتفاع + عمق نشیمن ۲..۸][دسته + پشتی ۲..۹].
     *
     * @var list<list<int>>
     */
    private const array TABLE_A = [
        [2, 2, 3, 4, 5, 6, 7, 8],
        [2, 2, 3, 4, 5, 6, 7, 8],
        [3, 3, 3, 4, 5, 6, 7, 8],
        [4, 4, 4, 4, 5, 6, 7, 8],
        [5, 5, 5, 5, 6, 7, 8, 9],
        [6, 6, 6, 7, 7, 8, 8, 9],
        [7, 7, 7, 8, 8, 9, 9, 9],
    ];

    /**
     * نمودار B برگه رسمی: [تلفن ۰..۶][مانیتور ۰..۷].
     *
     * @var list<list<int>>
     */
    private const array TABLE_B = [
        [1, 1, 1, 2, 3, 4, 5, 6],
        [1, 1, 2, 2, 3, 4, 5, 6],
        [1, 2, 2, 3, 3, 4, 6, 7],
        [2, 2, 3, 3, 4, 5, 6, 8],
        [3, 3, 4, 4, 5, 6, 7, 8],
        [4, 4, 5, 5, 6, 7, 8, 9],
        [5, 5, 6, 7, 8, 8, 9, 9],
    ];

    /**
     * نمودار C برگه رسمی: [ماوس ۰..۷][صفحه‌کلید ۰..۷].
     *
     * @var list<list<int>>
     */
    private const array TABLE_C = [
        [1, 1, 1, 2, 3, 4, 5, 6],
        [1, 1, 2, 3, 4, 5, 6, 7],
        [1, 2, 2, 3, 4, 5, 6, 7],
        [2, 3, 3, 3, 5, 6, 7, 8],
        [3, 4, 4, 5, 5, 6, 7, 8],
        [4, 5, 5, 6, 6, 7, 8, 9],
        [5, 6, 6, 7, 7, 8, 8, 9],
        [6, 7, 7, 8, 8, 9, 9, 9],
    ];

    /** بیشینه ستون مانیتور در نمودار B. */
    private const int MONITOR_MAX = 7;

    /** امتیاز هر گزینه، وقتی چند گزینه امتیاز یکسان دارند. */
    private const array CHAIR_HEIGHT = [1 => 1, 2 => 2, 3 => 2, 4 => 3];

    private const array SEAT_DEPTH = [1 => 1, 2 => 2, 3 => 2];

    private const array ARMRESTS = [1 => 1, 2 => 2, 3 => 2];

    private const array BACKREST = [1 => 1, 2 => 2, 3 => 2, 4 => 2];

    /** کد مدت → امتیاز: کمتر از ۱ ساعت، ۱ تا ۴ ساعت، بیش از ۴ ساعت در روز. */
    private const array DURATION = [1 => -1, 2 => 0, 3 => 1];

    /** سطح اقدام مقاله. */
    private const int ACTION_LEVEL = 5;

    public function id(): string
    {
        return 'rosa';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function title(): string
    {
        return 'ارزیابی ایستگاه کار اداری به روش ROSA';
    }

    public function reference(): Reference
    {
        return new Reference(
            title: 'Sonne M, Villalta DL, Andrews DM. Development and evaluation of an office ergonomic risk checklist: ROSA – Rapid Office Strain Assessment. Applied Ergonomics 2012;43(1):98–108',
            publisher: 'Applied Ergonomics (Elsevier)',
            year: 2012,
            relation: 'Chair = ChartA(height + depth, armrest + back) + duration ; ROSA = max(Chair, max(ChartB(phone, monitor), ChartC(mouse, keyboard)))',
            note: 'نمودارها از برگه امتیازدهی رسمی ROSA (Sonne، منتشرشده در سایت ارگونومی دانشگاه Cornell) و با نسخه فارسی همان برگه یکی‌اند. مثال‌های حل‌شده NTP 1173 (INSST، ۲۰۲۲) و راهنمای Cornell مورد مرجع مستقل‌اند.',
        );
    }

    public function limitations(): array
    {
        return [
            'ROSA روش غربالگری است: امتیاز اولویت بررسی را نشان می‌دهد، نه احتمال آسیب یا تشخیص.',
            'یک ایستگاه کار و یک فرد در هر اجرا؛ مدت استفاده را از خود فرد بپرسید.',
            'امتیاز کمتر از ۵ یعنی ریسک کمتر، نه بی‌ریسک بودن؛ ناراحتی گزارش‌شده فرد را جدا ببینید.',
            'برای کار با لپ‌تاپ، نسخه‌های اصلاح‌شده ROSA در پژوهش‌ها پیشنهاد شده‌اند که این‌جا پیاده نشده‌اند.',
        ];
    }

    public function inputs(): array
    {
        $code = static fn (string $key, string $label, float $max): InputDefinition => InputDefinition::single($key, $label, Unit::Score, 1.0, $max);
        $flag = static fn (string $key, string $label): InputDefinition => InputDefinition::single($key, $label, Unit::Score, 0.0, 1.0);

        return [
            'chair_height' => $code('chair_height', 'ارتفاع صندلی', 4.0),
            'desk_no_leg_room' => $flag('desk_no_leg_room', 'فضای ناکافی زیر میز برای پاها'),
            'chair_height_fixed' => $flag('chair_height_fixed', 'ارتفاع صندلی تنظیم‌شدنی نیست'),
            'seat_depth' => $code('seat_depth', 'عمق نشیمن', 3.0),
            'seat_depth_fixed' => $flag('seat_depth_fixed', 'عمق نشیمن تنظیم‌شدنی نیست'),
            'armrests' => $code('armrests', 'دسته صندلی', 3.0),
            'armrest_hard' => $flag('armrest_hard', 'سطح دسته سفت یا آسیب‌دیده'),
            'armrest_wide' => $flag('armrest_wide', 'دسته‌ها خیلی از هم دورند'),
            'armrest_fixed' => $flag('armrest_fixed', 'دسته تنظیم‌شدنی نیست'),
            'backrest' => $code('backrest', 'پشتی صندلی', 4.0),
            'desk_too_high' => $flag('desk_too_high', 'سطح کار خیلی بلند، شانه‌ها بالا'),
            'backrest_fixed' => $flag('backrest_fixed', 'پشتی تنظیم‌شدنی نیست'),
            'chair_duration' => $code('chair_duration', 'مدت نشستن روی صندلی', 3.0),
            'monitor' => $code('monitor', 'جای مانیتور', 3.0),
            'monitor_far' => $flag('monitor_far', 'مانیتور خیلی دور'),
            'monitor_neck_twist' => $flag('monitor_neck_twist', 'چرخش گردن بیش از ۳۰ درجه'),
            'monitor_glare' => $flag('monitor_glare', 'بازتاب نور روی صفحه'),
            'monitor_no_holder' => $flag('monitor_no_holder', 'کار با کاغذ بدون نگه‌دارنده سند'),
            'monitor_duration' => $code('monitor_duration', 'مدت کار با مانیتور', 3.0),
            'phone' => $code('phone', 'جای تلفن', 2.0),
            'phone_neck_hold' => $flag('phone_neck_hold', 'نگه‌داشتن گوشی میان گردن و شانه'),
            'phone_no_handsfree' => $flag('phone_no_handsfree', 'بدون هدست یا بلندگو'),
            'phone_duration' => $code('phone_duration', 'مدت استفاده از تلفن', 3.0),
            'mouse' => $code('mouse', 'جای ماوس', 2.0),
            'mouse_separate_surface' => $flag('mouse_separate_surface', 'ماوس و صفحه‌کلید روی دو سطح جدا'),
            'mouse_pinch' => $flag('mouse_pinch', 'گرفتن ماوس با نوک انگشتان'),
            'mouse_palmrest' => $flag('mouse_palmrest', 'تکیه‌گاه کف دست جلوی ماوس'),
            'mouse_duration' => $code('mouse_duration', 'مدت کار با ماوس', 3.0),
            'keyboard' => $code('keyboard', 'مچ و شانه هنگام تایپ', 2.0),
            'keyboard_deviation' => $flag('keyboard_deviation', 'انحراف مچ به پهلو هنگام تایپ'),
            'keyboard_too_high' => $flag('keyboard_too_high', 'صفحه‌کلید خیلی بلند، شانه‌ها بالا'),
            'keyboard_overhead' => $flag('keyboard_overhead', 'دست بردن به وسایل بالای سر'),
            'keyboard_platform_fixed' => $flag('keyboard_platform_fixed', 'سطح صفحه‌کلید تنظیم‌شدنی نیست'),
            'keyboard_duration' => $code('keyboard_duration', 'مدت کار با صفحه‌کلید', 3.0),
        ];
    }

    public function outputs(): array
    {
        return [
            'rosa_score' => Unit::Score,
            'chair_score' => Unit::Score,
            'peripherals_score' => Unit::Score,
            'monitor_phone_score' => Unit::Score,
            'mouse_keyboard_score' => Unit::Score,
            'chair_table_score' => Unit::Score,
            'seat_score' => Unit::Score,
            'armrest_back_score' => Unit::Score,
            'monitor_score' => Unit::Score,
            'phone_score' => Unit::Score,
            'mouse_score' => Unit::Score,
            'keyboard_score' => Unit::Score,
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
        $sum = static fn (string ...$keys): int => array_sum(array_map($code, $keys));
        $duration = static fn (string $key): int => self::DURATION[$code($key)];

        $seat = self::CHAIR_HEIGHT[$code('chair_height')] + $sum('desk_no_leg_room', 'chair_height_fixed')
            + self::SEAT_DEPTH[$code('seat_depth')] + $code('seat_depth_fixed');
        $armrestBack = self::ARMRESTS[$code('armrests')] + $sum('armrest_hard', 'armrest_wide', 'armrest_fixed')
            + self::BACKREST[$code('backrest')] + $sum('desk_too_high', 'backrest_fixed');

        $chairTable = self::TABLE_A[$seat - 2][$armrestBack - 2];
        $chair = $chairTable + $duration('chair_duration');

        $rawMonitor = $sum('monitor', 'monitor_far', 'monitor_neck_twist', 'monitor_glare', 'monitor_no_holder') + $duration('monitor_duration');
        $monitor = min($rawMonitor, self::MONITOR_MAX);
        $phone = $sum('phone', 'phone_neck_hold', 'phone_no_handsfree') + $duration('phone_duration');
        $mouse = $sum('mouse', 'mouse_separate_surface', 'mouse_pinch', 'mouse_palmrest') + $duration('mouse_duration');
        $keyboard = $sum('keyboard', 'keyboard_deviation', 'keyboard_too_high', 'keyboard_overhead', 'keyboard_platform_fixed') + $duration('keyboard_duration');

        $monitorPhone = self::TABLE_B[$phone][$monitor];
        $mouseKeyboard = self::TABLE_C[$mouse][$keyboard];

        // دو نمودار پایانی برگه (لوازم جانبی و نهایی) در همه خانه‌ها بیشینه سطر و ستون‌اند.
        $peripherals = max($monitorPhone, $mouseKeyboard);
        $score = max($chair, $peripherals);

        $notes = [$score >= self::ACTION_LEVEL
            ? 'امتیاز ۵ یا بیشتر، سطح اقدام ROSA: ایستگاه کار باید هرچه زودتر بیشتر بررسی و اصلاح شود.'
            : 'امتیاز کمتر از ۵: بررسی بیشتر فوری لازم نیست، ولی ریسک صفر هم نیست.'];

        if ($rawMonitor > self::MONITOR_MAX) {
            $notes[] = 'امتیاز مانیتور با مدت استفاده به ۸ می‌رسید؛ نمودار B تا ۷ است و ۷ گرفته شد.';
        }

        if ($score > 1) {
            $drivers = $this->drivers($score, ['صندلی' => $chair, 'مانیتور و تلفن' => $monitorPhone, 'ماوس و صفحه‌کلید' => $mouseKeyboard]);
            $notes[] = sprintf('امتیاز نهایی را %s می‌سازد؛ تا این بخش اصلاح نشود، بهبود بخش‌های دیگر امتیاز را پایین نمی‌آورد.', $drivers);
        }

        return new Outcome(
            values: [
                'rosa_score' => (float) $score,
                'chair_score' => (float) $chair,
                'peripherals_score' => (float) $peripherals,
                'monitor_phone_score' => (float) $monitorPhone,
                'mouse_keyboard_score' => (float) $mouseKeyboard,
                'chair_table_score' => (float) $chairTable,
                'seat_score' => (float) $seat,
                'armrest_back_score' => (float) $armrestBack,
                'monitor_score' => (float) $monitor,
                'phone_score' => (float) $phone,
                'mouse_score' => (float) $mouse,
                'keyboard_score' => (float) $keyboard,
            ],
            notes: $notes,
        );
    }

    /**
     * بخش‌هایی که امتیاز نهایی همان امتیاز آن‌هاست؛ نمودارهای پایانی بیشینه‌اند،
     * پس فقط همین بخش‌ها امتیاز را بالا نگه می‌دارند.
     *
     * @param  array<string, int>  $sections
     */
    private function drivers(int $score, array $sections): string
    {
        return SegmentLeaders::join(array_keys(array_filter($sections, static fn (int $value): bool => $value === $score)));
    }
}
