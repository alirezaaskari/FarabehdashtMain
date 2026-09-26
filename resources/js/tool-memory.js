/**
 * به‌خاطر سپردن ورودی‌های آخرین محاسبه هر ابزار (بخش ۱۸-۱۱).
 *
 * فقط در localStorage همین مرورگر؛ به سرور نمی‌رود و خودکار هم فرم را پر
 * نمی‌کند، چون روی دستگاه مشترک عدد دیگری جلوی چشم آمدن گیج‌کننده است. نوار
 * «پر کردن فرم» فقط روی فرم تازه دیده می‌شود و خروج از حساب همه را پاک می‌کند.
 */
const PREFIX = 'fbh.tool-inputs.';

const storage = {
    read(key) {
        try {
            return JSON.parse(localStorage.getItem(key) ?? 'null');
        } catch {
            return null;
        }
    },
    write(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch {
            // حالت خصوصی یا فضای پر: بی‌حافظه ادامه می‌دهیم.
        }
    },
    remove(key) {
        try {
            localStorage.removeItem(key);
        } catch {
            // همان بالا.
        }
    },
};

export function forgetAllToolInputs() {
    try {
        Object.keys(localStorage)
            .filter((key) => key.startsWith(PREFIX))
            .forEach((key) => localStorage.removeItem(key));
    } catch {
        // همان بالا.
    }
}

export function initToolMemory() {
    document.querySelectorAll('form[data-signout]').forEach((form) => {
        form.addEventListener('submit', forgetAllToolInputs);
    });

    const form = document.querySelector('form[data-tool-memory]');
    if (!form) return;

    const key = PREFIX + form.dataset.toolMemory;

    form.addEventListener('submit', () => {
        const fields = [...new FormData(form).entries()]
            .filter(([name, value]) => name !== '_token' && String(value).trim() !== '')
            .map(([name, value]) => [name, String(value)]);

        if (fields.length > 0) storage.write(key, { fields });
    });

    const saved = storage.read(key);
    const bar = form.querySelector('[data-tool-memory-bar]');

    if (!bar || !('toolMemoryOffer' in form.dataset) || !Array.isArray(saved?.fields)) return;

    bar.hidden = false;

    bar.querySelector('[data-tool-memory-fill]')?.addEventListener('click', () => {
        fill(form, saved.fields);
        bar.hidden = true;
        form.querySelector('input:not([type=hidden]), select')?.focus();
    });

    bar.querySelector('[data-tool-memory-forget]')?.addEventListener('click', () => {
        storage.remove(key);
        bar.hidden = true;
    });
}

/** ردیف‌های فهرستی (`name[]`) به ترتیب پر می‌شوند؛ اضافه بر ردیف‌های فرم کنار می‌رود. */
function fill(form, fields) {
    const offsets = {};

    for (const [name, value] of fields) {
        const controls = [...form.elements].filter((control) => control.name === name);

        if (controls[0]?.type === 'radio') {
            controls.forEach((radio) => { radio.checked = radio.value === value; });
            continue;
        }

        const index = offsets[name] ?? 0;
        offsets[name] = index + 1;

        if (controls[index]) controls[index].value = value;
    }
}
