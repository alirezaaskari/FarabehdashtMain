/*
 * سرویس‌ورکر فرابهداشت (بخش ۱۸-۱۰، DEC-05).
 *
 * فقط یک کار دارد: ابزارها پس از نخستین بازدید بدون اینترنت باز شوند.
 * - دارایی‌های ساخته‌شده (build، فونت، آیکن) نام هش‌دار دارند: اول از حافظه.
 * - صفحه‌های ابزار: اول از شبکه و نگه‌داشتن آخرین نسخه؛ بی‌اینترنت همان نسخه.
 * - هر صفحه دیگری بی‌اینترنت صفحه «آفلاین» را می‌گیرد.
 * هیچ درخواست POST، پاسخ خطا یا صفحه‌ای بیرون از /tools نگه داشته نمی‌شود.
 * خروج از حساب با `Clear-Site-Data: "cache"` همه این‌ها را پاک می‌کند.
 */
const VERSION = 'v1';
const ASSETS = `fbh-assets-${VERSION}`;
const PAGES = `fbh-pages-${VERSION}`;
const OFFLINE = '/tools/offline';

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(PAGES).then((cache) => cache.addAll([OFFLINE, '/tools'])).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key.startsWith('fbh-') && ![ASSETS, PAGES].includes(key)).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

const isAsset = (url) => /^\/(build|fonts|icons)\//.test(url.pathname);
const isToolPage = (url) => /^\/tools(\/[a-z0-9-]+)?\/?$/.test(url.pathname) && !url.pathname.startsWith('/tools/calculations');

async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) return cached;

    const response = await fetch(request);
    if (response.ok && response.type === 'basic') {
        (await caches.open(ASSETS)).put(request, response.clone());
    }
    return response;
}

async function networkFirst(request, url) {
    // نسخه میدانی و معمولی یک صفحه‌اند؛ پارامترهای دیگر (مثل مقدار پیش‌پر) کلید جدا نمی‌سازند.
    const key = new URL(url.pathname + (url.searchParams.get('field') === '1' ? '?field=1' : ''), url.origin).href;

    try {
        const response = await fetch(request);
        if (response.ok && response.type === 'basic' && !response.redirected) {
            (await caches.open(PAGES)).put(key, response.clone());
        }
        return response;
    } catch {
        return (await caches.match(key)) ?? (await caches.match(url.pathname)) ?? caches.match(OFFLINE);
    }
}

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    if (isAsset(url)) {
        event.respondWith(cacheFirst(request));
    } else if (request.mode === 'navigate' && isToolPage(url)) {
        event.respondWith(networkFirst(request, url));
    } else if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE)));
    }
});

// صفحه‌ای که پیش از فعال‌شدن سرویس‌ورکر بار شده، دارایی‌ها و نشانی خودش را می‌فرستد.
self.addEventListener('message', (event) => {
    if (event.data?.type !== 'cache' || !Array.isArray(event.data.urls)) return;

    event.waitUntil(Promise.all(event.data.urls.map(async (href) => {
        const url = new URL(href);
        if (url.origin !== self.location.origin) return;

        if (isAsset(url)) {
            const cache = await caches.open(ASSETS);
            if (!(await cache.match(url.href))) await cache.add(url.href).catch(() => {});
        } else if (isToolPage(url)) {
            await networkFirst(new Request(url.href), url).catch(() => {});
        }
    })));
});
