/**
 * فرم گام‌به‌گام ابزارهای پوسچر (RULA و مانند آن).
 *
 * بدون این فایل همه گام‌ها زیر هم‌اند و فرم همان‌طور کار می‌کند. این‌جا فقط
 * یک گام در هر لحظه نشان داده می‌شود، «گام بعد» پیش از رفتن گزینه‌های همان
 * گام را می‌خواهد، و وقتی همه گزینه‌ها پر است امتیاز با نسخه JS همان رابطه
 * (offline/engine.js) زنده نشان داده می‌شود؛ عدد نهایی همچنان از سرور می‌آید.
 */
import { castFormValue, formatMeasurement, hasFormula, InvalidInput, run } from './offline/engine.js';

const persian = (value) => String(value).replace(/\d/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[digit]);

export function initToolSteps(root) {
    const form = root.closest('form');
    const steps = [...root.querySelectorAll('[data-tool-step]')];
    const nav = root.querySelector('[data-steps-nav]');
    const progress = root.querySelector('[data-steps-progress]');
    const score = root.querySelector('[data-steps-score]');
    const submit = form?.querySelector('button[type=submit]');

    if (!form || steps.length < 2 || !nav || !progress) return;

    const spec = JSON.parse(form.closest('[data-offline-tool]')?.querySelector('script[data-offline-spec]')?.textContent ?? 'null');
    const prev = nav.querySelector('[data-steps-prev]');
    const next = nav.querySelector('[data-steps-next]');
    const bars = [...progress.children];

    // خطای سرور: از اولین گامی که خطا دارد؛ پس از نتیجه: گام آخر کنار دکمه محاسبه.
    const invalid = steps.findIndex((step) => step.querySelector('[aria-invalid="true"]'));
    let current = invalid >= 0 ? invalid : (root.dataset.stepsStart === 'last' ? steps.length - 1 : 0);

    function show(index, focus) {
        current = index;
        steps.forEach((step, i) => step.toggleAttribute('data-current', i === index));
        bars.forEach((bar, i) => bar.toggleAttribute('data-done', i <= index));
        prev.hidden = index === 0;
        next.hidden = index === steps.length - 1;
        if (submit) submit.hidden = index !== steps.length - 1;
        if (focus) steps[index].querySelector('legend')?.focus();
    }

    function stepIsComplete(step) {
        const missing = [...step.querySelectorAll('input[required]')].find((input) => !input.checkValidity());
        if (!missing) return true;
        missing.reportValidity();
        return false;
    }

    prev.addEventListener('click', () => show(current - 1, true));
    next.addEventListener('click', () => {
        if (stepIsComplete(steps[current])) show(current + 1, true);
    });

    if (score && spec && hasFormula(spec.formula)) {
        const update = () => liveScore(form, spec, score);
        form.addEventListener('change', update);
        update();
    }

    root.setAttribute('data-steps-ready', '');
    nav.hidden = false;
    progress.hidden = false;
    show(current, false);
}

function liveScore(form, spec, output) {
    const data = new FormData(form);
    const raw = {};

    for (const key of Object.keys(spec.inputs)) {
        // کلید روشن/خاموش یک فیلد پنهان صفر پیش از خودش دارد؛ آخرین مقدار درست است.
        const values = data.getAll(key);
        const number = values.length > 0 ? castFormValue(String(values.at(-1))) : null;
        if (number === null) {
            output.hidden = true;
            return;
        }
        raw[key] = number;
    }

    try {
        const { outputs } = run(spec.formula, spec.inputs, raw);
        const [key, primary] = Object.entries(spec.outputs)[0];
        output.replaceChildren(`${primary.label} با پاسخ‌های فعلی: `);
        const strong = document.createElement('strong');
        strong.textContent = persian(formatMeasurement(outputs[key]));
        output.append(strong, '. برای نتیجه کامل و ذخیره، «محاسبه کن» را بزنید.');
        output.hidden = false;
    } catch (error) {
        if (!(error instanceof InvalidInput)) throw error;
        output.hidden = true;
    }
}
