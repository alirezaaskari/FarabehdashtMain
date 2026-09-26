/**
 * صفحه ابزار بدون اینترنت (بخش ۱۸-۱۰).
 *
 * وقتی گوشی آفلاین است، «محاسبه کن» به‌جای رفتن به سرور همین‌جا با نسخه JS
 * همان رابطه حساب می‌کند (`engine.js`) و نتیجه را در همان جای نتیجه سرور نشان
 * می‌دهد. آنلاین هیچ چیزی عوض نمی‌شود: فرم مثل همیشه به سرور می‌رود.
 */
import { castFormValue, formatMeasurement, hasFormula, InvalidInput, run } from './engine.js';
import { enqueue } from './queue.js';

const el = (tag, className = '', text = '') => {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text) node.textContent = text;
    return node;
};

export function initOfflineTool(root) {
    const spec = JSON.parse(root.querySelector('script[data-offline-spec]')?.textContent ?? 'null');
    const form = root.querySelector('form[data-tool-form]');
    const result = document.getElementById('result');

    if (!spec || !form || !result || !hasFormula(spec.formula)) {
        return;
    }

    form.addEventListener('submit', (event) => {
        if (navigator.onLine) {
            return;
        }

        event.preventDefault();
        const fields = [...new FormData(form).entries()]
            .filter(([name]) => name !== '_token')
            .map(([name, value]) => [name, String(value)]);

        render(result, spec, fields, document.body.dataset.userId ?? null);
        result.scrollIntoView({ block: 'start' });
    });
}

function collect(spec, fields) {
    const raw = {};

    for (const [key, definition] of Object.entries(spec.inputs)) {
        if (definition.list) {
            const values = fields.filter(([name]) => name === `${key}[]`)
                .map(([, value]) => castFormValue(value))
                .filter((value) => value !== null);
            if (values.length > 0) raw[key] = values;
        } else {
            const field = fields.find(([name]) => name === key);
            const value = field ? castFormValue(field[1]) : null;
            if (value !== null) raw[key] = value;
        }
    }

    return raw;
}

function render(container, spec, fields, userId) {
    container.replaceChildren();
    const wrap = el('div', 'flex flex-col gap-4');

    let outcome;

    try {
        outcome = run(spec.formula, spec.inputs, collect(spec, fields));
    } catch (error) {
        if (!(error instanceof InvalidInput)) throw error;

        const alert = el('div', 'rounded-lg border border-danger-line bg-danger-soft px-5 py-4');
        alert.setAttribute('role', 'alert');
        alert.append(el('p', 'text-label font-semibold text-danger', 'محاسبه انجام نشد'));
        const list = el('ul', 'mt-2 list-disc ps-5 text-note text-danger');
        error.errors.forEach((item) => list.append(el('li', '', item.message)));
        alert.append(list);
        wrap.append(alert);
        container.append(wrap);
        return;
    }

    const rows = Object.entries(spec.outputs).map(([key, output]) => ({
        label: output.label,
        unit: output.unit,
        value: formatMeasurement(outcome.outputs[key]),
    }));

    const [primary, ...rest] = rows;
    wrap.append(stat(`نتیجه محاسبه — ${primary.label}`, primary, true));

    if (rest.length > 0) {
        const grid = el('div', 'grid gap-4 sm:grid-cols-2');
        rest.forEach((row) => grid.append(stat(row.label, row, false)));
        wrap.append(grid);
    }

    outcome.notes.forEach((note) => {
        const alert = el('div', 'rounded-lg border border-caution-line bg-caution-soft px-5 py-4 text-note text-caution', note);
        alert.setAttribute('role', 'status');
        wrap.append(alert);
    });

    const notice = el('div', 'rounded-lg border border-line bg-surface-2 px-5 py-4');
    notice.append(el('p', 'text-note text-muted',
        'این نتیجه بدون اینترنت و روی همین دستگاه حساب شد؛ با همان رابطه و نسخه‌ای که سرور اجرا می‌کند.'));

    if (userId) {
        notice.append(saveForm(spec, fields, userId));
    } else {
        notice.append(el('p', 'mt-2 text-note text-muted', 'برای ذخیره محاسبه، با اینترنت وارد حساب شوید.'));
    }

    wrap.append(notice);
    container.append(wrap);
}

/** همان `x-stat`: کارت اول سبز و متریک، بقیه کارت معمولی. */
function stat(label, row, primary) {
    const box = el('div', primary
        ? 'rounded-xl border bg-primary border-transparent px-6 py-8 md:px-9'
        : 'rounded-xl border bg-surface border-line px-6 py-5.5');
    box.append(el('span', `block ${primary ? 'text-copy font-semibold text-primary-line' : 'text-note font-semibold text-muted'}`, label));

    const line = el('div', `${primary ? 'mt-3.5' : 'mt-2'} flex items-baseline gap-2`);
    line.dataset.numeric = '';
    line.append(el('span', primary ? 'text-metric text-on-primary' : 'text-stat text-ink', row.value));

    if (row.unit) {
        line.append(el('span', primary ? 'text-stat font-semibold text-primary-line' : 'text-label font-semibold text-muted', row.unit));
    }

    box.append(line);
    return box;
}

function saveForm(spec, fields, userId) {
    const form = el('form', 'mt-3 flex flex-col gap-3');
    const id = 'offline-label';
    const label = el('label', 'text-label font-semibold text-ink', 'نام این محاسبه (اختیاری)');
    label.htmlFor = id;
    const input = el('input', 'h-field w-full rounded-md border border-line-strong bg-surface px-3 text-control text-ink');
    input.id = id;
    input.type = 'text';
    const button = el('button', 'inline-flex min-h-touch items-center justify-center rounded-md bg-primary px-5 text-label font-semibold text-on-primary disabled:opacity-60',
        'ذخیره وقتی اینترنت برگشت');
    button.type = 'submit';
    const status = el('p', 'text-note text-muted');
    status.setAttribute('aria-live', 'polite');

    form.append(label, input, button, status);
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const ok = enqueue({
            slug: spec.slug,
            action: `/tools/${spec.slug}/save`,
            user: userId,
            fields,
            label: input.value.trim(),
        });
        button.disabled = true;
        status.textContent = ok
            ? 'در صف ماند. با برگشتن اینترنت خودکار در میزکار ذخیره می‌شود.'
            : 'حافظه مرورگر در دسترس نیست؛ این محاسبه در صف نماند.';
    });

    return form;
}
