/**
 * حالت تاریک.
 *
 * انتخاب کاربر در localStorage می‌ماند؛ اگر چیزی انتخاب نکرده باشد، تنظیم
 * سیستم‌عامل ملاک است. صفحات عمومی با data-theme="light" ثابت می‌مانند و
 * این کلید روی آن‌ها اثر ندارد.
 */

const STORAGE_KEY = 'fbh-theme';

function stored() {
    try {
        return localStorage.getItem(STORAGE_KEY);
    } catch {
        return null;
    }
}

function persist(theme) {
    try {
        localStorage.setItem(STORAGE_KEY, theme);
    } catch {
        // حالت ناشناس یا مسدودبودن ذخیره‌سازی — انتخاب فقط تا پایان همین بازدید می‌ماند.
    }
}

function systemPrefersDark() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

function current(root) {
    return root.dataset.theme ?? (systemPrefersDark() ? 'dark' : 'light');
}

export function initTheme() {
    const root = document.documentElement;

    // صفحاتی که صراحتاً روشن اعلام شده‌اند (صفحات عمومی) دست‌نخورده می‌مانند.
    if (root.dataset.lockTheme === 'true') {
        return;
    }

    const saved = stored();

    if (saved === 'dark' || saved === 'light') {
        root.dataset.theme = saved;
    }

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const next = current(root) === 'dark' ? 'light' : 'dark';
            root.dataset.theme = next;
            persist(next);
        });
    });
}
