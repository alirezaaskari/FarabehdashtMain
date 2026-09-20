/**
 * بررسی خودکار دسترس‌پذیری و ریسپانسیو.
 *
 * پیش‌نیاز: سرور محلی در حال اجرا باشد.
 *
 *   php artisan serve --port=8124
 *   npm run a11y
 *
 * متغیرهای محیطی اختیاری:
 *   FBH_URL    نشانی پایه (پیش‌فرض http://127.0.0.1:8124)
 *   FBH_PAGES  مسیرها با کامای جداکننده (پیش‌فرض /design-system,/login)
 *
 * چه چیزی بررسی می‌شود — همان قواعد چک‌لیست دسترس‌پذیری پروژه:
 * سرریز افقی · هدف لمسی زیر ۴۴ پیکسل · ورودی بدون برچسب ·
 * دکمه فقط-آیکون بدون aria-label · تعداد H1 · پرش ترتیب هدینگ ·
 * خصوصیت فیزیکی left/right به‌جای منطقی · dir و lang صفحه.
 */

import { chromium } from 'playwright';

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
const PAGES = (process.env.FBH_PAGES ?? '/design-system,/login,/tools,/tools/advisor,/tools/wbgt-indoor,/tools/noise-dose').split(',');
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

    out.dir = document.documentElement.dir;
    out.lang = document.documentElement.lang;

    return out;
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

for (const page of PAGES) {
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
        failures += issues.length;

        console.log(issues.length ? `  ✗ ${label} — ${issues.join(' · ')}` : `  ✓ ${label}`);
        await context.close();
    }
}

await browser.close();

console.log(failures === 0 ? '\nهمه بررسی‌ها قبول' : `\n${failures} ایراد`);
process.exit(failures === 0 ? 0 : 1);
