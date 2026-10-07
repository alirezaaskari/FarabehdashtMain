/**
 * نسخه آفلاین روش‌های مشاهده‌ای ارگونومی؛ هم‌ارز
 * packages/calc-engine/src/Formulas/Ergonomics (RulaV1، RebaV1).
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
    load: 'بار یا نیرو',
    load_shock: 'ضربه یا نیروی ناگهانی',
    upper_arm: 'زاویه بازو',
    shoulder_raised: 'شانه بالا رفته',
    arm_abducted_or_rotated: 'بازو از بدن دور شده یا چرخیده',
    arm_supported: 'بازو تکیه‌گاه دارد یا فرد به جلو تکیه داده',
    lower_arm: 'زاویه ساعد',
    wrist: 'زاویه مچ',
    wrist_twisted_or_bent: 'مچ به پهلو خم شده یا چرخیده',
    coupling: 'دستگیره و نحوه گرفتن',
    static_posture: 'یک یا چند عضو بیش از یک دقیقه ثابت',
    repeated_action: 'حرکت کوچک تکراری، بیش از ۴ بار در دقیقه',
    rapid_change: 'تغییر سریع و بزرگ پوسچر یا تکیه‌گاه ناپایدار',
};

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

    if (names.length < 2) return names[0] ?? '';

    const last = names.pop();

    return `${names.join('، ')} و ${last}`;
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
            const scoreA = postureA + code('load') + code('load_shock');
            const scoreB = postureB + code('coupling');
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
};
