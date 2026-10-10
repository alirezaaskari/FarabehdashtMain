/**
 * نسخه آفلاین روش‌های مشاهده‌ای ارگونومی؛ هم‌ارز
 * packages/calc-engine/src/Formulas/Ergonomics (RulaV1، RebaV1، RosaV1).
 */

// جدول‌های A، B و C مقاله McAtamney & Corlett (1993)، همان ترتیب RulaV1.
const RULA_TABLE_A = [
    [[1, 2, 2, 2, 2, 3, 3, 3], [2, 2, 2, 2, 3, 3, 3, 3], [2, 3, 3, 3, 3, 3, 4, 4]],
    [[2, 3, 3, 3, 3, 4, 4, 4], [3, 3, 3, 3, 3, 4, 4, 4], [3, 4, 4, 4, 4, 4, 5, 5]],
    [[3, 3, 4, 4, 4, 4, 5, 5], [3, 4, 4, 4, 4, 4, 5, 5], [4, 4, 4, 4, 4, 5, 5, 5]],
    [[4, 4, 4, 4, 4, 5, 5, 5], [4, 4, 4, 4, 4, 5, 5, 5], [4, 4, 4, 5, 5, 5, 6, 6]],
    [[5, 5, 5, 5, 5, 6, 6, 7], [5, 6, 6, 6, 6, 7, 7, 7], [6, 6, 6, 7, 7, 7, 7, 8]],
    [[7, 7, 7, 7, 7, 8, 8, 9], [8, 8, 8, 8, 8, 9, 9, 9], [9, 9, 9, 9, 9, 9, 9, 9]],
];

const RULA_TABLE_B = [
    [1, 3, 2, 3, 3, 4, 5, 5, 6, 6, 7, 7],
    [2, 3, 2, 3, 4, 5, 5, 5, 6, 7, 7, 7],
    [3, 3, 3, 4, 4, 5, 5, 6, 6, 7, 7, 7],
    [5, 5, 5, 6, 6, 7, 7, 7, 7, 7, 8, 8],
    [7, 7, 7, 7, 7, 8, 8, 8, 8, 8, 8, 8],
    [8, 8, 8, 8, 8, 8, 8, 9, 9, 9, 9, 9],
];

const RULA_TABLE_C = [
    [1, 2, 3, 3, 4, 5, 5],
    [2, 2, 3, 4, 4, 5, 5],
    [3, 3, 3, 4, 4, 5, 6],
    [3, 3, 3, 4, 5, 6, 6],
    [4, 4, 4, 5, 6, 7, 7],
    [4, 4, 5, 6, 6, 7, 7],
    [5, 5, 6, 6, 7, 7, 7],
    [5, 5, 6, 7, 7, 7, 7],
];

const RULA_ACTION_LEVELS = {
    1: 'سطح اقدام ۱: پوسچر پذیرفتنی است، به شرطی که مدت طولانی حفظ یا تکرار نشود.',
    2: 'سطح اقدام ۲: بررسی بیشتر لازم است و ممکن است تغییر نیاز باشد.',
    3: 'سطح اقدام ۳: بررسی و تغییر باید به‌زودی انجام شود.',
    4: 'سطح اقدام ۴: بررسی و تغییر باید فوراً انجام شود.',
};

// [خروجی، نام، بیشینه امتیاز]، هم‌ترتیب SEGMENTS در کلاس PHP.
const RULA_SEGMENTS = [
    ['upper_arm_score', 'بازو', 6],
    ['lower_arm_score', 'ساعد', 3],
    ['wrist_score', 'مچ', 4],
    ['neck_score', 'گردن', 6],
    ['trunk_score', 'تنه', 6],
];

const RULA_LABELS = {
    upper_arm: 'زاویه بازو',
    shoulder_raised: 'شانه بالا رفته',
    arm_abducted: 'بازو از بدن دور شده',
    arm_supported: 'بازو تکیه‌گاه دارد یا فرد به جلو تکیه داده',
    lower_arm: 'زاویه ساعد',
    lower_arm_out: 'ساعد از خط وسط بدن رد شده یا به کنار بدن رفته',
    wrist: 'زاویه مچ',
    wrist_bent: 'مچ به سمت انگشت شست یا کوچک خم شده',
    wrist_twist: 'چرخش مچ',
    arm_muscle: 'کار دست ایستا یا تکراری',
    arm_force: 'نیرو یا بار دست',
    neck: 'زاویه گردن',
    neck_twisted: 'گردن چرخیده',
    neck_side_bent: 'گردن به پهلو خم شده',
    trunk: 'زاویه تنه',
    trunk_twisted: 'تنه چرخیده',
    trunk_side_bent: 'تنه به پهلو خم شده',
    legs: 'وضعیت پاها',
    body_muscle: 'کار تنه و پا ایستا یا تکراری',
    body_force: 'نیرو یا بار روی تنه و پا',
};

const REBA_TABLE_A = [
    [[1, 2, 3, 4], [1, 2, 3, 4], [3, 3, 5, 6]],
    [[2, 3, 4, 5], [3, 4, 5, 6], [4, 5, 6, 7]],
    [[2, 4, 5, 6], [4, 5, 6, 7], [5, 6, 7, 8]],
    [[3, 5, 6, 7], [5, 6, 7, 8], [6, 7, 8, 9]],
    [[4, 6, 7, 8], [6, 7, 8, 9], [7, 8, 9, 9]],
];

const REBA_TABLE_B = [
    [[1, 2, 2], [1, 2, 3]],
    [[1, 2, 3], [2, 3, 4]],
    [[3, 4, 5], [4, 5, 5]],
    [[4, 5, 5], [5, 6, 7]],
    [[6, 7, 8], [7, 8, 8]],
    [[7, 8, 8], [8, 9, 9]],
];

const REBA_TABLE_C = [
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

const REBA_ACTION_LEVELS = [
    'سطح اقدام ۰، ریسک ناچیز: اقدامی لازم نیست.',
    'سطح اقدام ۱، ریسک کم: ممکن است اقدام لازم باشد.',
    'سطح اقدام ۲، ریسک متوسط: اقدام لازم است.',
    'سطح اقدام ۳، ریسک زیاد: اقدام باید به‌زودی انجام شود.',
    'سطح اقدام ۴، ریسک بسیار زیاد: اقدام باید همین حالا انجام شود.',
];

const REBA_SEGMENTS = [
    ['trunk_score', 'تنه', 5],
    ['neck_score', 'گردن', 3],
    ['legs_score', 'پاها', 4],
    ['upper_arm_score', 'بازو', 6],
    ['lower_arm_score', 'ساعد', 2],
    ['wrist_score', 'مچ', 3],
];

const REBA_LABELS = {
    trunk: 'زاویه تنه',
    trunk_twisted_or_bent: 'تنه چرخیده یا به پهلو خم شده',
    neck: 'زاویه گردن',
    neck_twisted_or_bent: 'گردن چرخیده یا به پهلو خم شده',
    legs: 'وضعیت پاها',
    knees: 'خم شدن زانو',
    load_class: 'بار یا نیرو',
    load_shock: 'ضربه یا نیروی ناگهانی',
    upper_arm: 'زاویه بازو',
    shoulder_raised: 'شانه بالا رفته',
    arm_abducted_or_rotated: 'بازو از بدن دور شده یا چرخیده',
    arm_supported: 'بازو تکیه‌گاه دارد یا فرد به جلو تکیه داده',
    lower_arm: 'زاویه ساعد',
    wrist: 'زاویه مچ',
    wrist_twisted_or_bent: 'مچ به پهلو خم شده یا چرخیده',
    grip: 'دستگیره و نحوه گرفتن',
    static_posture: 'یک یا چند عضو بیش از یک دقیقه ثابت',
    repeated_action: 'حرکت کوچک تکراری، بیش از ۴ بار در دقیقه',
    rapid_change: 'تغییر سریع و بزرگ پوسچر یا تکیه‌گاه ناپایدار',
};

const ROSA_TABLE_A = [
    [2, 2, 3, 4, 5, 6, 7, 8],
    [2, 2, 3, 4, 5, 6, 7, 8],
    [3, 3, 3, 4, 5, 6, 7, 8],
    [4, 4, 4, 4, 5, 6, 7, 8],
    [5, 5, 5, 5, 6, 7, 8, 9],
    [6, 6, 6, 7, 7, 8, 8, 9],
    [7, 7, 7, 8, 8, 9, 9, 9],
];

const ROSA_TABLE_B = [
    [1, 1, 1, 2, 3, 4, 5, 6],
    [1, 1, 2, 2, 3, 4, 5, 6],
    [1, 2, 2, 3, 3, 4, 6, 7],
    [2, 2, 3, 3, 4, 5, 6, 8],
    [3, 3, 4, 4, 5, 6, 7, 8],
    [4, 4, 5, 5, 6, 7, 8, 9],
    [5, 5, 6, 7, 8, 8, 9, 9],
];

const ROSA_TABLE_C = [
    [1, 1, 1, 2, 3, 4, 5, 6],
    [1, 1, 2, 3, 4, 5, 6, 7],
    [1, 2, 2, 3, 4, 5, 6, 7],
    [2, 3, 3, 3, 5, 6, 7, 8],
    [3, 4, 4, 5, 5, 6, 7, 8],
    [4, 5, 5, 6, 6, 7, 8, 9],
    [5, 6, 6, 7, 7, 8, 8, 9],
    [6, 7, 7, 8, 8, 9, 9, 9],
];

// امتیاز هر کد گزینه و هر کد مدت، هم‌ارز ثابت‌های RosaV1.
const ROSA_POINTS = {
    chair_height: { 1: 1, 2: 2, 3: 2, 4: 3 },
    seat_depth: { 1: 1, 2: 2, 3: 2 },
    armrests: { 1: 1, 2: 2, 3: 2 },
    backrest: { 1: 1, 2: 2, 3: 2, 4: 2 },
};

const ROSA_DURATION = { 1: -1, 2: 0, 3: 1 };

const ROSA_MONITOR_MAX = 7;

const ROSA_LABELS = {
    chair_height: 'ارتفاع صندلی',
    desk_no_leg_room: 'فضای ناکافی زیر میز برای پاها',
    chair_height_fixed: 'ارتفاع صندلی تنظیم‌شدنی نیست',
    seat_depth: 'عمق نشیمن',
    seat_depth_fixed: 'عمق نشیمن تنظیم‌شدنی نیست',
    armrests: 'دسته صندلی',
    armrest_hard: 'سطح دسته سفت یا آسیب‌دیده',
    armrest_wide: 'دسته‌ها خیلی از هم دورند',
    armrest_fixed: 'دسته تنظیم‌شدنی نیست',
    backrest: 'پشتی صندلی',
    desk_too_high: 'سطح کار خیلی بلند، شانه‌ها بالا',
    backrest_fixed: 'پشتی تنظیم‌شدنی نیست',
    chair_duration: 'مدت نشستن روی صندلی',
    monitor: 'جای مانیتور',
    monitor_far: 'مانیتور خیلی دور',
    monitor_neck_twist: 'چرخش گردن بیش از ۳۰ درجه',
    monitor_glare: 'بازتاب نور روی صفحه',
    monitor_no_holder: 'کار با کاغذ بدون نگه‌دارنده سند',
    monitor_duration: 'مدت کار با مانیتور',
    phone: 'جای تلفن',
    phone_neck_hold: 'نگه‌داشتن گوشی میان گردن و شانه',
    phone_no_handsfree: 'بدون هدست یا بلندگو',
    phone_duration: 'مدت استفاده از تلفن',
    mouse: 'جای ماوس',
    mouse_separate_surface: 'ماوس و صفحه‌کلید روی دو سطح جدا',
    mouse_pinch: 'گرفتن ماوس با نوک انگشتان',
    mouse_palmrest: 'تکیه‌گاه کف دست جلوی ماوس',
    mouse_duration: 'مدت کار با ماوس',
    keyboard: 'مچ و شانه هنگام تایپ',
    keyboard_deviation: 'انحراف مچ به پهلو هنگام تایپ',
    keyboard_too_high: 'صفحه‌کلید خیلی بلند، شانه‌ها بالا',
    keyboard_overhead: 'دست بردن به وسایل بالای سر',
    keyboard_platform_fixed: 'سطح صفحه‌کلید تنظیم‌شدنی نیست',
    keyboard_duration: 'مدت کار با صفحه‌کلید',
};

const joinNames = (names) => (names.length < 2 ? names[0] ?? '' : `${names.slice(0, -1).join('، ')} و ${names.at(-1)}`);

const UPPER_ARM_CLAMP_NOTE = (table) => `امتیاز بازو با کم‌کردن تکیه‌گاه به کمتر از ۱ می‌رسید؛ کمترین امتیاز جدول ${table} یعنی ۱ گرفته شد.`;

const choiceErrors = (labels, inputs) => Object.keys(labels)
    .filter((key) => Math.floor(inputs[key]) !== inputs[key])
    .map((key) => ({ key, message: `برای «${labels[key]}» یکی از گزینه‌ها را انتخاب کنید.` }));

const leadersNote = (leaders) => `بیشترین سهم در امتیاز پوسچر را ${leaders} دارد؛ اصلاح از همین‌جا بیشترین اثر را دارد.`;

const persian = (number) => String(number).replace(/\d/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[digit]);

// هم‌ارز SegmentLeaders::describe.
function segmentLeaders(segments, spec) {
    let best = 0;
    let names = [];

    for (const [key, name, max] of spec) {
        if (segments[key] <= 1) continue;

        const ratio = segments[key] / max;
        const label = `${name} (${persian(segments[key])} از ${persian(max)})`;

        if (Math.abs(ratio - best) < 1e-9) {
            names.push(label);
        } else if (ratio > best) {
            best = ratio;
            names = [label];
        }
    }

    return joinNames(names);
}

export default {
    // RULA = TableC(TableA + عضله + نیرو، TableB + عضله + نیرو)
    'rula@1.0.0': {
        crossCheck: (inputs) => choiceErrors(RULA_LABELS, inputs),
        compute(inputs) {
            const code = (key) => Math.trunc(inputs[key]);
            const rawUpperArm = code('upper_arm') + code('shoulder_raised') + code('arm_abducted') - code('arm_supported');
            const segments = {
                upper_arm_score: Math.max(1, rawUpperArm),
                lower_arm_score: code('lower_arm') + code('lower_arm_out'),
                wrist_score: code('wrist') + code('wrist_bent'),
                neck_score: code('neck') + code('neck_twisted') + code('neck_side_bent'),
                trunk_score: code('trunk') + code('trunk_twisted') + code('trunk_side_bent'),
            };

            const postureA = RULA_TABLE_A[segments.upper_arm_score - 1][segments.lower_arm_score - 1][(segments.wrist_score - 1) * 2 + code('wrist_twist') - 1];
            const postureB = RULA_TABLE_B[segments.neck_score - 1][(segments.trunk_score - 1) * 2 + code('legs') - 1];
            const armWrist = postureA + code('arm_muscle') + code('arm_force');
            const neckTrunkLeg = postureB + code('body_muscle') + code('body_force');
            const score = RULA_TABLE_C[Math.min(armWrist, 8) - 1][Math.min(neckTrunkLeg, 7) - 1];
            const actionLevel = Math.trunc((score + 1) / 2);

            const notes = [RULA_ACTION_LEVELS[actionLevel]];

            if (rawUpperArm < 1) {
                notes.push(UPPER_ARM_CLAMP_NOTE('A'));
            }

            const leaders = segmentLeaders(segments, RULA_SEGMENTS);

            if (leaders !== '') {
                notes.push(leadersNote(leaders));
            }

            return {
                values: {
                    rula_score: score,
                    action_level: actionLevel,
                    arm_wrist_score: armWrist,
                    neck_trunk_leg_score: neckTrunkLeg,
                    posture_a: postureA,
                    posture_b: postureB,
                    ...segments,
                },
                notes,
            };
        },
    },

    // REBA = TableC(TableA + بار، TableB + دستگیره) + فعالیت
    'reba@1.0.0': {
        crossCheck: (inputs) => choiceErrors(REBA_LABELS, inputs),
        compute(inputs) {
            const code = (key) => Math.trunc(inputs[key]);
            const rawUpperArm = code('upper_arm') + code('shoulder_raised') + code('arm_abducted_or_rotated') - code('arm_supported');
            const segments = {
                trunk_score: code('trunk') + code('trunk_twisted_or_bent'),
                neck_score: code('neck') + code('neck_twisted_or_bent'),
                legs_score: code('legs') + code('knees'),
                upper_arm_score: Math.max(1, rawUpperArm),
                lower_arm_score: code('lower_arm'),
                wrist_score: code('wrist') + code('wrist_twisted_or_bent'),
            };

            const postureA = REBA_TABLE_A[segments.trunk_score - 1][segments.neck_score - 1][segments.legs_score - 1];
            const postureB = REBA_TABLE_B[segments.upper_arm_score - 1][segments.lower_arm_score - 1][segments.wrist_score - 1];
            const scoreA = postureA + code('load_class') + code('load_shock');
            const scoreB = postureB + code('grip');
            const scoreC = REBA_TABLE_C[scoreA - 1][scoreB - 1];
            const activity = code('static_posture') + code('repeated_action') + code('rapid_change');
            const score = scoreC + activity;
            const actionLevel = score === 1 ? 0 : score <= 3 ? 1 : score <= 7 ? 2 : score <= 10 ? 3 : 4;

            const notes = [REBA_ACTION_LEVELS[actionLevel]];

            if (rawUpperArm < 1) {
                notes.push(UPPER_ARM_CLAMP_NOTE('B'));
            }

            const leaders = segmentLeaders(segments, REBA_SEGMENTS);

            if (leaders !== '') {
                notes.push(leadersNote(leaders));
            }

            return {
                values: {
                    reba_score: score,
                    action_level: actionLevel,
                    score_a: scoreA,
                    score_b: scoreB,
                    score_c: scoreC,
                    activity_score: activity,
                    posture_a: postureA,
                    posture_b: postureB,
                    ...segments,
                },
                notes,
            };
        },
    },

    // ROSA = بیشینه(صندلی، بیشینه(B، C))
    'rosa@1.0.0': {
        crossCheck: (inputs) => choiceErrors(ROSA_LABELS, inputs),
        compute(inputs) {
            const code = (key) => Math.trunc(inputs[key]);
            const sum = (...keys) => keys.reduce((total, key) => total + code(key), 0);
            const duration = (key) => ROSA_DURATION[code(key)];
            const points = (key) => ROSA_POINTS[key][code(key)];

            const seat = points('chair_height') + sum('desk_no_leg_room', 'chair_height_fixed') + points('seat_depth') + code('seat_depth_fixed');
            const armrestBack = points('armrests') + sum('armrest_hard', 'armrest_wide', 'armrest_fixed') + points('backrest') + sum('desk_too_high', 'backrest_fixed');

            const chairTable = ROSA_TABLE_A[seat - 2][armrestBack - 2];
            const chair = chairTable + duration('chair_duration');

            const rawMonitor = sum('monitor', 'monitor_far', 'monitor_neck_twist', 'monitor_glare', 'monitor_no_holder') + duration('monitor_duration');
            const monitor = Math.min(rawMonitor, ROSA_MONITOR_MAX);
            const phone = sum('phone', 'phone_neck_hold', 'phone_no_handsfree') + duration('phone_duration');
            const mouse = sum('mouse', 'mouse_separate_surface', 'mouse_pinch', 'mouse_palmrest') + duration('mouse_duration');
            const keyboard = sum('keyboard', 'keyboard_deviation', 'keyboard_too_high', 'keyboard_overhead', 'keyboard_platform_fixed') + duration('keyboard_duration');

            const monitorPhone = ROSA_TABLE_B[phone][monitor];
            const mouseKeyboard = ROSA_TABLE_C[mouse][keyboard];
            const peripherals = Math.max(monitorPhone, mouseKeyboard);
            const score = Math.max(chair, peripherals);

            const notes = [score >= 5
                ? 'امتیاز ۵ یا بیشتر، سطح اقدام ROSA: ایستگاه کار باید هرچه زودتر بیشتر بررسی و اصلاح شود.'
                : 'امتیاز کمتر از ۵: بررسی بیشتر فوری لازم نیست، ولی ریسک صفر هم نیست.'];

            if (rawMonitor > ROSA_MONITOR_MAX) {
                notes.push('امتیاز مانیتور با مدت استفاده به ۸ می‌رسید؛ نمودار B تا ۷ است و ۷ گرفته شد.');
            }

            if (score > 1) {
                const sections = [['صندلی', chair], ['مانیتور و تلفن', monitorPhone], ['ماوس و صفحه‌کلید', mouseKeyboard]];
                const drivers = joinNames(sections.filter(([, value]) => value === score).map(([name]) => name));
                notes.push(`امتیاز نهایی را ${drivers} می‌سازد؛ تا این بخش اصلاح نشود، بهبود بخش‌های دیگر امتیاز را پایین نمی‌آورد.`);
            }

            return {
                values: {
                    rosa_score: score,
                    chair_score: chair,
                    peripherals_score: peripherals,
                    monitor_phone_score: monitorPhone,
                    mouse_keyboard_score: mouseKeyboard,
                    chair_table_score: chairTable,
                    seat_score: seat,
                    armrest_back_score: armrestBack,
                    monitor_score: monitor,
                    phone_score: phone,
                    mouse_score: mouse,
                    keyboard_score: keyboard,
                },
                notes,
            };
        },
    },
};
