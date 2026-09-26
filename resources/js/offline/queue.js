/**
 * صف ذخیره آفلاین (بخش ۱۸-۱۰).
 *
 * محاسبه‌ای که بدون اینترنت انجام و «ذخیره» شده این‌جا می‌ماند و با برگشتن
 * اتصال به همان مسیر ذخیره عادی فرستاده می‌شود. سرور ورودی‌ها را دوباره اجرا
 * می‌کند؛ عددی که گوشی حساب کرده هرگز خودش ذخیره نمی‌شود.
 *
 * هر مورد شناسه کاربرِ صفحه‌ای را دارد که در آن صف شد: روی گوشی مشترک، صف
 * کاربر دیگر فرستاده نمی‌شود و دور ریخته می‌شود.
 */
const KEY = 'fbh.offline-saves';

export function queued() {
    try {
        const items = JSON.parse(localStorage.getItem(KEY) ?? '[]');
        return Array.isArray(items) ? items : [];
    } catch {
        return [];
    }
}

function store(items) {
    try {
        if (items.length === 0) {
            localStorage.removeItem(KEY);
        } else {
            localStorage.setItem(KEY, JSON.stringify(items));
        }
        return true;
    } catch {
        return false;
    }
}

/** @param {{slug: string, action: string, user: string, fields: [string, string][], label: string}} item */
export function enqueue(item) {
    return store([...queued(), { ...item, queuedAt: new Date().toISOString() }]);
}

/**
 * فرستادن صف با توکن CSRF صفحه فعلی. فقط وقتی کاربر وارد شده (فرم خروج در
 * سربرگ هست) و همان کاربری است که صف کرده.
 *
 * @returns {Promise<{saved: number, failed: string[]}>}
 */
export async function flush() {
    const token = document.querySelector('header input[name="_token"]')?.value;
    const user = document.body.dataset.userId;
    const items = queued();

    if (!token || !user || items.length === 0 || !navigator.onLine) {
        return { saved: 0, failed: [] };
    }

    const remaining = [];
    const failed = [];
    let saved = 0;

    for (const item of items) {
        if (item.user !== user) {
            continue;
        }

        const body = new FormData();
        body.append('_token', token);
        item.fields.forEach(([name, value]) => body.append(name, value));
        body.append('label', item.label ?? '');

        try {
            const response = await fetch(item.action, {
                method: 'POST',
                body,
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (response.ok) {
                saved++;
            } else if (response.status === 403 || response.status === 422) {
                // رد قطعی (سقف پلن یا ورودی نامعتبر): دوباره فرستادن فایده ندارد.
                const data = await response.json().catch(() => ({}));
                failed.push(data.message ?? 'یک محاسبه آفلاین ذخیره نشد؛ ورودی‌هایش را روی صفحه ابزار دوباره بفرستید.');
            } else {
                remaining.push(item);
            }
        } catch {
            remaining.push(item);
        }
    }

    store(remaining);

    return { saved, failed };
}
