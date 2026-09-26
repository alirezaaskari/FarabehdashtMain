/**
 * موتور محاسبه آفلاین (بخش ۱۸-۱۰، DEC-05).
 *
 * نسخه جاوااسکریپت همان موتور `packages/calc-engine` برای وقتی که اینترنت نیست.
 * منبع حقیقت PHP است: `npm run test:formulas` همه موردهای طلایی پکیج و موردهای
 * تصادفی‌ای را که خود موتور PHP حساب کرده به این‌جا می‌دهد و باید همان خروجی،
 * همان یادداشت و همان کلید خطا بگیرد.
 *
 * `spec` تعریف ورودی‌های فرمول است، همان شکلی که صفحه ابزار و بردارهای هم‌ارزی
 * می‌دهند: { key: { label, unit_label, min, max, list, min_items, max_items } }.
 */
import { formulas } from './formulas/index.js';

export class InvalidInput extends Error {
    /** @param {{key: string, message: string}[]} errors */
    constructor(errors) {
        super(errors.map((error) => error.message).join(' '));
        this.errors = errors;
    }

    keys() {
        return [...new Set(this.errors.map((error) => error.key))].sort();
    }
}

export function hasFormula(id) {
    return Object.hasOwn(formulas, id);
}

/**
 * @param {string} id  «شناسه@نسخه»
 * @param {Record<string, object>} spec
 * @param {Record<string, unknown>} raw  عدد، فهرست عدد، یا چیزی که عدد نیست
 * @returns {{outputs: Record<string, number>, notes: string[]}}
 */
export function run(id, spec, raw) {
    const formula = formulas[id];

    if (!formula) {
        throw new Error(`فرمول «${id}» نسخه آفلاین ندارد.`);
    }

    const inputs = validate(spec, raw);
    const crossErrors = formula.crossCheck ? formula.crossCheck(inputs) : [];

    if (crossErrors.length > 0) {
        throw new InvalidInput(crossErrors);
    }

    const { values, notes = [] } = formula.compute(inputs);

    return { outputs: values, notes };
}

/** همان قاعده‌های `InputSet::validate` و همان متن پیام‌ها. */
export function validate(spec, raw) {
    const errors = [];
    const values = {};

    for (const key of Object.keys(raw)) {
        if (!Object.hasOwn(spec, key)) {
            errors.push({ key, message: `ورودی ناشناخته «${key}» برای این فرمول فرستاده شد.` });
        }
    }

    for (const [key, definition] of Object.entries(spec)) {
        if (!Object.hasOwn(raw, key)) {
            errors.push({ key, message: `«${definition.label}» الزامی است.` });
            continue;
        }

        const value = definition.list
            ? castList(key, definition, raw[key], errors)
            : Array.isArray(raw[key])
                ? (errors.push({ key, message: `«${definition.label}» یک مقدار می‌گیرد، نه فهرست.` }), null)
                : castNumber(definition, raw[key], key, errors);

        if (value !== null) {
            values[key] = value;
        }
    }

    if (errors.length > 0) {
        throw new InvalidInput(errors);
    }

    return values;
}

function castList(key, definition, raw, errors) {
    if (!Array.isArray(raw)) {
        errors.push({ key, message: `«${definition.label}» فهرستی از مقادیر می‌گیرد.` });
        return null;
    }

    if (raw.length < definition.min_items) {
        errors.push({ key, message: `«${definition.label}» دست‌کم ${definition.min_items} مقدار می‌خواهد.` });
        return null;
    }

    if (raw.length > definition.max_items) {
        errors.push({ key, message: `«${definition.label}» بیش از ${definition.max_items} مقدار نمی‌پذیرد.` });
        return null;
    }

    const numbers = [];

    for (const [index, item] of raw.entries()) {
        const number = castNumber(definition, item, `${key}.${index}`, errors);

        if (number === null) {
            return null;
        }

        numbers.push(number);
    }

    return numbers;
}

function castNumber(definition, raw, errorKey, errors) {
    if (typeof raw !== 'number') {
        errors.push({ key: errorKey, message: `«${definition.label}» باید عدد باشد.` });
        return null;
    }

    if (!Number.isFinite(raw)) {
        errors.push({ key: errorKey, message: `«${definition.label}» باید عددی متناهی باشد.` });
        return null;
    }

    if (raw < definition.min || raw > definition.max) {
        errors.push({
            key: errorKey,
            message: `«${definition.label}» باید بین ${trim(definition.min)} و ${trim(definition.max)} ${definition.unit_label} باشد.`,
        });
        return null;
    }

    return raw;
}

function trim(value) {
    return String(Number(value.toFixed(2)));
}

/**
 * همان `ToolInputCaster`: ارقام فارسی و عربی و جداکننده‌های فارسی پذیرفته
 * می‌شوند، خانه خالی حذف می‌شود و چیزی که عدد نیست دست‌نخورده می‌ماند تا
 * اعتبارسنجی ردش کند.
 */
export function castFormValue(raw) {
    const normalised = String(raw)
        .trim()
        .replace(/[۰-۹]/g, (d) => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(d)))
        .replace(/[٠-٩]/g, (d) => String('٠١٢٣٤٥٦٧٨٩'.indexOf(d)))
        .replace(/٫/g, '.')
        .replace(/[٬, ‌]/g, '');

    if (normalised === '') {
        return null;
    }

    return /^[+-]?(\d+\.?\d*|\.\d+)([eE][+-]?\d+)?$/.test(normalised) ? Number(normalised) : raw;
}

/** همان `MeasurementNumber::format`: چهار رقم اعشار، بدون صفرهای انتهایی. */
export function formatMeasurement(value, decimals = 4) {
    if (!Number.isFinite(value)) {
        return '—';
    }

    const fixed = value.toFixed(decimals);

    return fixed.includes('.') ? fixed.replace(/0+$/, '').replace(/\.$/, '') : fixed;
}
