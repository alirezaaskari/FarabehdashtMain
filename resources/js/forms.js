/**
 * فرم‌ها: خطای همزمان با تایپ و دکمه ارسال «در حال انجام».
 *
 * اعتبارسنجی همان قیدهای HTML فیلد است (required، min، type=email…)؛ قاعده
 * تازه‌ای این‌جا نیست و سرور همچنان داور نهایی است. فقط پیام به فارسی و
 * زیر همان فیلد می‌آید، نه در حباب انگلیسی مرورگر.
 */

const fa = (value) => Number(value).toLocaleString('fa-IR', { useGrouping: false });

function message(control) {
    const { validity } = control;

    if (validity.valueMissing) {
        return 'این مورد را پر کنید.';
    }

    if (validity.badInput) {
        return 'یک عدد بنویسید.';
    }

    if (validity.typeMismatch) {
        return control.type === 'email' ? 'نشانی ایمیل درست نیست.' : 'نشانی درست نیست.';
    }

    if (validity.tooShort) {
        return `دست‌کم ${fa(control.minLength)} نویسه بنویسید.`;
    }

    if (validity.tooLong) {
        return `بیشتر از ${fa(control.maxLength)} نویسه نشود.`;
    }

    if (validity.rangeUnderflow) {
        return `کمتر از ${fa(control.min)} نباشد.`;
    }

    if (validity.rangeOverflow) {
        return `بیشتر از ${fa(control.max)} نباشد.`;
    }

    if (validity.patternMismatch) {
        return control.title || 'قالب این مورد درست نیست.';
    }

    if (validity.stepMismatch) {
        return 'این مقدار با گام مجاز جور نیست.';
    }

    return control.validationMessage;
}

function errorFor(control) {
    return control.closest('[data-field]')?.querySelector('[data-field-error]') ?? null;
}

function describe(control, error, shown) {
    const ids = new Set((control.getAttribute('aria-describedby') ?? '').split(' ').filter(Boolean));
    shown ? ids.add(error.id) : ids.delete(error.id);

    if (ids.size > 0) {
        control.setAttribute('aria-describedby', [...ids].join(' '));
    } else {
        control.removeAttribute('aria-describedby');
    }
}

function show(control) {
    const error = errorFor(control);

    if (!error) {
        return false;
    }

    const valid = control.validity.valid;
    error.querySelector('[data-field-error-text]').textContent = valid ? '' : message(control);
    error.hidden = valid;
    if (valid) {
        control.removeAttribute('aria-invalid');
    } else {
        control.setAttribute('aria-invalid', 'true');
    }

    describe(control, error, !valid);

    return true;
}

/**
 * خطا پس از ترک فیلد می‌آید، نه با نخستین حرف؛ ولی فیلدی که خطا دارد با
 * هر حرف دوباره سنجیده می‌شود تا خطا همان لحظه درست‌شدن برود.
 */
function initLiveValidation() {
    document.addEventListener('focusout', (event) => {
        if (event.target.matches?.('[data-field] input') && event.target.value !== '') {
            show(event.target);
        }
    });

    document.addEventListener('input', (event) => {
        if (event.target.matches?.('[data-field] input[aria-invalid]')) {
            show(event.target);
        }
    });

    // ارسال فرم نامعتبر: مرورگر برای هر فیلد رویداد invalid می‌فرستد. فیلدهای
    // x-field پیام خودشان را می‌گیرند و نخستینشان فوکوس می‌شود.
    let focused = false;

    document.addEventListener('invalid', (event) => {
        if (!show(event.target)) {
            return;
        }

        event.preventDefault();

        if (!focused) {
            focused = true;
            event.target.focus();
            queueMicrotask(() => {
                focused = false;
            });
        }
    }, true);
}

/**
 * دکمه ارسال پس از کلیک «در حال انجام» می‌شود تا دو بار پرداخت یا دو بار
 * ثبت پیش نیاید. پاسخی که صفحه را عوض نمی‌کند (دانلود فایل) دکمه را پس از
 * چند ثانیه آزاد می‌کند.
 */
const RELEASE_AFTER = 8000;

function release(button) {
    button.disabled = false;
    button.removeAttribute('aria-busy');
}

function initBusySubmit() {
    document.addEventListener('submit', (event) => {
        const form = event.target;
        const button = event.submitter;

        if (event.defaultPrevented || !(button instanceof HTMLButtonElement) || form.target || form.method === 'get') {
            return;
        }

        button.setAttribute('aria-busy', 'true');
        // بعد از این تیک، تا نام و مقدار دکمه در داده فرم بماند.
        setTimeout(() => {
            button.disabled = true;
        });
        setTimeout(() => release(button), RELEASE_AFTER);
    });

    // بازگشت با دکمه Back صفحه را از حافظه می‌آورد، با دکمه‌های قفل‌شده.
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            document.querySelectorAll('button[aria-busy="true"]').forEach(release);
        }
    });
}

export function initForms() {
    initLiveValidation();
    initBusySubmit();
}
