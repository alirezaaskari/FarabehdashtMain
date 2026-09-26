/**
 * نصب روی گوشی و کار بدون اینترنت (بخش ۱۸-۱۰، DEC-05).
 *
 * سرویس‌ورکر (`public/sw.js`) صفحه‌های ابزاری را که یک بار باز شده‌اند نگه
 * می‌دارد. این فایل آن را ثبت می‌کند، دکمه «نصب روی گوشی» را روشن می‌کند، صف
 * ذخیره آفلاین را با برگشتن اتصال می‌فرستد و آفلاین‌بودن را اعلام می‌کند.
 */
let installPrompt = null;

export function initPwa() {
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', register);
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        installPrompt = event;
        document.querySelectorAll('[data-install]').forEach((button) => { button.hidden = false; });
    });

    document.querySelectorAll('[data-install]').forEach((button) => {
        button.addEventListener('click', async () => {
            if (!installPrompt) return;
            installPrompt.prompt();
            await installPrompt.userChoice;
            installPrompt = null;
            button.hidden = true;
        });
    });

    // iOS دکمه نصب ندارد؛ راهنمای «افزودن به صفحه اصلی» فقط همان‌جا دیده می‌شود.
    if (/iphone|ipad|ipod/i.test(navigator.userAgent) && !navigator.standalone) {
        document.querySelectorAll('[data-install-ios]').forEach((hint) => { hint.hidden = false; });
    }

    const tool = document.querySelector('[data-offline-tool]');
    if (tool) {
        import('./offline/tool-page.js').then(({ initOfflineTool }) => initOfflineTool(tool));
    }

    window.addEventListener('online', () => { banner(false); sendQueue(); });
    window.addEventListener('offline', () => banner(true));
    if (!navigator.onLine) banner(true);
    sendQueue();
}

async function register() {
    try {
        const registration = await navigator.serviceWorker.register('/sw.js', { scope: '/' });
        const worker = registration.active ?? registration.installing ?? registration.waiting;

        // بار اول سرویس‌ورکر پس از بار شدن صفحه می‌آید؛ دارایی‌های همین صفحه را
        // خودش نگرفته، پس نشانی‌شان را می‌دهیم تا ابزار از همین حالا آفلاین کار کند.
        const assets = performance.getEntriesByType('resource')
            .map((entry) => entry.name)
            .filter((url) => url.startsWith(location.origin) && /\/(build|fonts|icons)\//.test(url));

        const message = { type: 'cache', urls: [...assets, ...(document.querySelector('[data-offline-tool]') ? [location.href] : [])] };
        (navigator.serviceWorker.controller ?? worker)?.postMessage(message);
    } catch {
        // بدون سرویس‌ورکر سایت همان سایت آنلاین است.
    }
}

async function sendQueue() {
    const { queued, flush } = await import('./offline/queue.js');

    if (queued().length === 0 || !navigator.onLine) return;

    const { saved, failed } = await flush();

    if (saved > 0) {
        notice(`${saved.toLocaleString('fa-IR')} محاسبه‌ای که بدون اینترنت ذخیره کرده بودید در میزکار ثبت شد.`, false);
    }
    failed.forEach((message) => notice(message, true));
}

function banner(offline) {
    let node = document.getElementById('offline-banner');

    if (!offline) {
        node?.remove();
        return;
    }

    if (node) return;

    node = document.createElement('div');
    node.id = 'offline-banner';
    node.setAttribute('role', 'status');
    node.className = 'border-b border-line bg-surface-2 px-6 py-3 text-note text-ink md:px-gutter';
    node.textContent = 'اینترنت وصل نیست. ابزارهایی که قبلاً باز کرده‌اید همچنان حساب می‌کنند.';
    document.querySelector('main')?.before(node);
}

function notice(text, error) {
    const node = document.createElement('div');
    node.setAttribute('role', error ? 'alert' : 'status');
    node.className = error
        ? 'border-b border-danger-line bg-danger-soft px-6 py-3 text-note text-danger md:px-gutter'
        : 'border-b border-primary-line bg-primary-soft px-6 py-3 text-note text-on-primary-soft md:px-gutter';
    node.textContent = text;
    document.querySelector('main')?.before(node);
}
