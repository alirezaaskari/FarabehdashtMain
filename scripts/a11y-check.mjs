/**
 * بررسی خودکار دسترس‌پذیری و ریسپانسیو.
 *
 * پیش‌نیاز: سرور محلی در حال اجرا باشد.
 *
 *   php artisan serve --port=8124
 *   npm run a11y
 *
 * متغیرهای محیطی اختیاری:
 *   FBH_URL      نشانی پایه (پیش‌فرض http://127.0.0.1:8124)
 *   FBH_PAGES    مسیرها با کامای جداکننده (پیش‌فرض: فهرست پایین)
 *   FBH_SITEMAP  تعداد نشانی از هر فایل نقشه سایت که به فهرست اضافه می‌شود
 *                (پیش‌فرض ۱؛ ۰ یعنی خاموش). این‌طور هر نوع صفحه‌ای که به
 *                گوگل معرفی می‌شود — مقاله، ماده، دوره، محصول — بی‌نام‌بردن
 *                از نامک‌ها بررسی می‌شود.
 *
 * چه چیزی بررسی می‌شود — همان قواعد چک‌لیست دسترس‌پذیری پروژه:
 * سرریز افقی · هدف لمسی زیر ۴۴ پیکسل · ورودی بدون برچسب ·
 * دکمه فقط-آیکون بدون aria-label · تعداد H1 · پرش ترتیب هدینگ ·
 * خصوصیت فیزیکی left/right به‌جای منطقی · dir و lang صفحه ·
 * عدد با واحد علمی بیرون از data-numeric ·
 * قواعد axe-core (کنتراست، نقش‌ها، نام دسترس‌پذیر) · پیمایش با Tab
 * (پیوند پرش اول می‌آید، هر فوکوس حلقه دیده‌شونده دارد، تله فوکوس نیست).
 *
 * axe از node_modules تزریق می‌شود، نه از CDN: هیچ صفحه‌ای داده به سرویس
 * بیرونی نمی‌فرستد و بررسی هم نباید بفرستد (بخش ۱۷، DEC-33).
 */

import { createRequire } from 'node:module';
import { chromium } from 'playwright';

const AXE_PATH = createRequire(import.meta.url).resolve('axe-core/axe.min.js');

const BASE = process.env.FBH_URL ?? 'http://127.0.0.1:8124';

/*
 * صفحات میزکار پشت ورودند و تا امروز هیچ‌وقت بررسی نشده بودند، چون این
 * اسکریپت فقط GET ساده می‌زد و به صفحه ورود هدایت می‌شد.
 *
 * با دادن FBH_A11Y_MOBILE، اسکریپت اول با رمز یک‌بارمصرف وارد می‌شود و کد را
 * از storage/logs می‌خواند — یعنی فقط با درایور پیامک `log` کار می‌کند، که
 * همان چیزی است که در محیط توسعه هست. روی CI این متغیر تنظیم نیست و رفتار
 * اسکریپت عوض نمی‌شود.
 */
const LOGIN_MOBILE = process.env.FBH_A11Y_MOBILE ?? null;
const PAGES = (process.env.FBH_PAGES ?? '/,/design-system,/login,/tools,/tools/advisor,/tools/wbgt-indoor,/tools/noise-dose,/encyclopedia,/chemicals,/courses,/commerce').split(',');
const SITEMAP_SAMPLE = Number(process.env.FBH_SITEMAP ?? 1);
const WIDTHS = [
    ['موبایل ۳۹۰', 390],
    ['تبلت ۷۶۸', 768],
    ['دسکتاپ ۱۲۸۰', 1280],
];

function audit() {
    const out = {
        overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
    };

    // چک‌باکس و رادیو معمولاً کوچک‌اند ولی ناحیه لمس واقعی، برچسبشان است.
    const touchArea = (el) => {
        const own = el.getBoundingClientRect();
        const label = el.labels?.[0]?.getBoundingClientRect();

        return label ? Math.max(own.height, label.height) : own.height;
    };

    out.smallTargets = [...document.querySelectorAll('a, button, input, [role="button"]')]
        .filter((el) => {
            const r = el.getBoundingClientRect();
            return r.width > 4 && r.height > 4 && touchArea(el) < 44;
        })
        .map((el) => `${el.tagName.toLowerCase()} «${(el.textContent || '').trim().slice(0, 24)}»`);

    // ورودی پنهان (مثل توکن CSRF) دیده نمی‌شود، پس برچسب هم نمی‌خواهد.
    out.unlabelled = [...document.querySelectorAll('input:not([type=checkbox]):not([type=radio]):not([type=hidden])')]
        .filter((el) => !el.labels?.length && !el.getAttribute('aria-label'))
        .map((el) => el.name || el.id || '(بی‌نام)');

    out.iconOnly = [...document.querySelectorAll('button')]
        .filter((el) => !el.textContent.trim() && !el.getAttribute('aria-label')).length;

    const levels = [...document.querySelectorAll('h1,h2,h3,h4')].map((h) => Number(h.tagName[1]));
    out.h1Count = levels.filter((l) => l === 1).length;
    out.headingJumps = levels.filter((l, i) => i > 0 && l - levels[i - 1] > 1).length;

    out.physical = [...document.querySelectorAll('[style]')]
        .filter((el) => /(margin|padding)-(left|right)/.test(el.getAttribute('style'))).length;

    // عدد با واحد علمی در متن راست‌چین وارونه می‌شود («dB 85»)، مگر
    // داخل data-numeric یا dir="ltr" باشد (قواعد لایه طراحی).
    const units = /\d[\d.,٫]*\s*(dB|dBA|ppm|mg\/m|°C|m\/s|W\/m|Hz|lux)/;
    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    out.bareUnits = [];
    for (let node = walker.nextNode(); node; node = walker.nextNode()) {
        const host = node.parentElement;
        if (!host || !units.test(node.textContent) || host.closest('script, style, [data-numeric], [dir="ltr"]')) continue;
        if (host.getClientRects().length === 0) continue;
        out.bareUnits.push(node.textContent.trim().slice(0, 30));
    }

    out.dir = document.documentElement.dir;
    out.lang = document.documentElement.lang;

    return out;
}

/**
 * پیمایش صفحه فقط با Tab، همان‌طور که کاربر صفحه‌کلید می‌کند.
 *
 * بیش از تعداد عنصرهای فوکوس‌پذیر Tab می‌زند؛ اگر فوکوس هیچ‌وقت به ته صفحه
 * نرسد و از سر نگیرد، جایی گیر افتاده است — تله فوکوس.
 */
async function keyboardWalk(tab) {
    const issues = [];
    const focusable = await tab.evaluate(() => document.querySelectorAll(
        'a[href], button:not([disabled]), input:not([type=hidden]):not([disabled]), select, textarea, summary, [tabindex]:not([tabindex="-1"])',
    ).length);

    const seen = [];
    let wrapped = false;

    // ورودی تاریخ سه بخش و یک دکمه تقویم دارد و چهار Tab می‌خورد.
    for (let i = 0; i < focusable * 4 + 10; i++) {
        await tab.keyboard.press('Tab');

        const step = await tab.evaluate(() => {
            const el = document.activeElement;

            if (!el || el === document.body) return null;

            const style = getComputedStyle(el);

            // فوکوس داخل سایه مرورگر (دکمه تقویم ورودی تاریخ) حلقه خود مرورگر
            // را دارد و از بیرون دیده نمی‌شود؛ عنصر میزبانش :focus نیست.
            const ring = !el.matches(':focus')
                || (style.outlineStyle !== 'none' && parseFloat(style.outlineWidth) > 0)
                || (style.boxShadow && style.boxShadow !== 'none');
            const r = el.getBoundingClientRect();

            return {
                key: el.outerHTML.slice(0, 160),
                label: `${el.tagName.toLowerCase()} «${(el.textContent || el.getAttribute('aria-label') || el.name || '').trim().slice(0, 24)}»`,
                href: el.getAttribute('href'),
                ring,
                visible: r.width > 0 && r.height > 0,
            };
        });

        // فوکوس از آخرین عنصر به نوار نشانی مرورگر رفت یا از سر گرفت: پایان دور.
        if (step === null || (seen.length > 0 && step.key === seen[0].key)) {
            wrapped = true;
            break;
        }

        seen.push(step);
    }

    if (seen[0] && seen[0].href !== '#main') {
        issues.push(`اولین Tab باید «رفتن به محتوای اصلی» باشد، هست: ${seen[0].label}`);
    }

    const noRing = [...new Set(seen.filter((s) => !s.ring).map((s) => s.label))];
    if (noRing.length) issues.push(`فوکوس بدون حلقه: ${noRing.join(' · ')}`);

    const hidden = [...new Set(seen.filter((s) => !s.visible).map((s) => s.label))];
    if (hidden.length) issues.push(`فوکوس روی عنصر نادیده: ${hidden.join(' · ')}`);

    if (!wrapped && seen.length > 0) issues.push('تله فوکوس: Tab هرگز از صفحه بیرون نرفت');

    return issues;
}

/**
 * قواعد axe-core با برچسب WCAG 2.1 A/AA. ایرادهایی که اسکریپت خودش
 * دقیق‌تر می‌گیرد (هدف لمسی، H1) تکرار نمی‌شوند.
 */
async function axeIssues(tab) {
    await tab.addScriptTag({ path: AXE_PATH });

    const violations = await tab.evaluate(async () => {
        const result = await window.axe.run(document, {
            runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'] },
        });

        return result.violations.map((v) => `${v.id} (${v.nodes.length}): ${v.nodes[0]?.target.join(' ')}`);
    });

    return violations.map((v) => `axe ${v}`);
}

/**
 * چند نشانی تازه (نه صفحه فهرست که بالا هست) از هر فایل نقشه سایت؛ مسیر نسبی برمی‌گرداند چون نشانی پایه
 * نقشه سایت (APP_URL) ممکن است با سرور محلی یکی نباشد.
 */
async function sitemapPages(count, known) {
    if (count <= 0) return [];

    const locs = (xml) => [...xml.matchAll(/<loc>([^<]+)<\/loc>/g)].map((m) => m[1]);
    const index = await fetch(`${BASE}/sitemap.xml`).then((r) => (r.ok ? r.text() : ''));
    const pages = [];

    for (const file of locs(index)) {
        const xml = await fetch(`${BASE}${new URL(file).pathname}`).then((r) => r.text());
        const fresh = locs(xml).map((loc) => new URL(loc).pathname).filter((path) => !known.includes(path));
        pages.push(...fresh.slice(0, count));
    }

    return pages;
}

function issuesFor(report) {
    const issues = [];

    if (report.overflow > 0) issues.push(`سرریز افقی ${report.overflow}px`);
    if (report.smallTargets.length) issues.push(`هدف لمسی کوچک: ${report.smallTargets.join(' · ')}`);
    if (report.unlabelled.length) issues.push(`ورودی بدون برچسب: ${report.unlabelled.join(', ')}`);
    if (report.iconOnly) issues.push(`دکمه فقط-آیکون بدون aria-label: ${report.iconOnly}`);
    if (report.h1Count !== 1) issues.push(`تعداد H1 باید ۱ باشد، هست: ${report.h1Count}`);
    if (report.headingJumps) issues.push(`پرش ترتیب هدینگ: ${report.headingJumps}`);
    if (report.physical) issues.push(`خصوصیت فیزیکی به‌جای منطقی: ${report.physical}`);
    if (report.bareUnits.length) issues.push(`عدد و واحد بدون data-numeric: ${report.bareUnits.join(' · ')}`);
    if (report.dir !== 'rtl' || report.lang !== 'fa') issues.push(`dir/lang نادرست: ${report.dir}/${report.lang}`);

    return issues;
}

const browser = await chromium.launch(
    process.env.PLAYWRIGHT_CHROMIUM ? { executablePath: process.env.PLAYWRIGHT_CHROMIUM } : {},
);

/**
 * ورود با رمز یک‌بارمصرف و برگرداندن وضعیت ذخیره‌شده مرورگر.
 */
async function signIn(mobile) {
    const { execSync } = await import('node:child_process');

    const context = await browser.newContext();
    const tab = await context.newPage();

    await tab.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
    await tab.fill('input[name="mobile"]', mobile);

    const terms = tab.locator('input[name="terms"]');
    if (await terms.count()) await terms.check();

    await tab.click('button[type="submit"]');
    await tab.waitForLoadState('networkidle');

    const line = execSync(`grep '"mobile"' storage/logs/laravel.log | tail -1`).toString();
    const code = (line.match(/: (\d{4,8})/) || [])[1];

    if (!code) throw new Error('کد ورود در storage/logs پیدا نشد. درایور پیامک باید log باشد.');

    await tab.fill('input[name="code"]', code);
    await tab.click('button[type="submit"]');
    await tab.waitForLoadState('networkidle');

    const state = await context.storageState();
    await context.close();

    return state;
}

const storageState = LOGIN_MOBILE ? await signIn(LOGIN_MOBILE) : undefined;

let failures = 0;

const pages = [...new Set([...PAGES, ...(await sitemapPages(SITEMAP_SAMPLE, PAGES))])];

for (const page of pages) {
    console.log(`\n${page}`);

    for (const [label, width] of WIDTHS) {
        const context = await browser.newContext({ viewport: { width, height: 900 }, storageState });
        const tab = await context.newPage();
        await tab.goto(`${BASE}${page}`, { waitUntil: 'networkidle' });

        // اگر صفحه پشت ورود باشد و وارد نشده باشیم، چیزی که بررسی می‌شود
        // صفحه ورود است نه صفحه هدف — و آن یک نتیجه دروغ است.
        if (!tab.url().includes(page)) {
            console.log(`  ⚠ ${label} — به ${tab.url()} هدایت شد؛ بررسی نشد.`);
            await context.close();
            continue;
        }

        const issues = issuesFor(await tab.evaluate(audit));

        // axe و صفحه‌کلید به عرض وابسته نیستند مگر در پیمایش؛ یک بار در
        // موبایل (منوی جمع‌شده) و یک بار در دسکتاپ کافی است.
        if (width !== 768) {
            issues.push(...(await axeIssues(tab)));
            await tab.goto(`${BASE}${page}`, { waitUntil: 'networkidle' });
            issues.push(...(await keyboardWalk(tab)));
        }

        failures += issues.length;

        console.log(issues.length ? `  ✗ ${label} — ${issues.join(' · ')}` : `  ✓ ${label}`);
        await context.close();
    }
}

await browser.close();

console.log(failures === 0 ? '\nهمه بررسی‌ها قبول' : `\n${failures} ایراد`);
process.exit(failures === 0 ? 0 : 1);
