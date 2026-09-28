# سرورهای MCP پروژه

`.mcp.json` در ریشه مخزن چهار سرور MCP را برای هر نشست Claude Code روی این مخزن (نشست ابری و
کامپیوتر خودتان) تعریف می‌کند. نسخه هر بسته قفل است تا ارتقا فقط با یک PR اتفاق بیفتد.

| سرور | بسته | کاربرد | نیاز |
|---|---|---|---|
| `context7` | `@upstash/context7-mcp` | مستند به‌روز کتابخانه‌ها (Laravel 13، Filament 5، Tailwind 4) به‌جای حافظه مدل | دسترسی به `context7.com` |
| `playwright` | `@playwright/mcp` | باز کردن واقعی سایت در مرورگر، کلیک، پرکردن فرم، اسنپ‌شات دسترس‌پذیری | — |
| `chrome-devtools` | `chrome-devtools-mcp` (خود گوگل) | بررسی سئوی فنی با Lighthouse (`lighthouse_audit`: سئو، دسترس‌پذیری، بهترین روش‌ها)، شبکه و کارایی | — |
| `snyk` | `snyk` (CLI رسمی، دستور `snyk mcp`) | اسکن امنیتی وابستگی‌های Composer و npm و کد | حساب Snyk و `SNYK_TOKEN`؛ دسترسی به `snyk.io` |

## چرا این‌ها

- **سئو:** «SEO MCP» محصول مشخصی نیست. سرور Chrome DevTools مال خود تیم Chrome است و ممیزی Lighthouse
  را روی هر نشانی (از جمله سایت محلی) اجرا می‌کند؛ بسته‌های «SEO MCP» دیگر یا غیررسمی‌اند یا داده را
  به سرویس بیرونی می‌فرستند. آمار استفاده و CrUX آن با `--no-usage-statistics --no-performance-crux` خاموش است.
- **فیگما** عمداً نصب نشده است.

## مرورگر

`scripts/mcp/browser.mjs` هر دو سرور مرورگری را راه می‌اندازد. روی کامپیوتر خودتان از Google Chrome نصب‌شده
استفاده می‌کنند. در نشست ابری Chrome نیست و نشست با root اجرا می‌شود، پس اسکریپت به Chromium ازپیش‌نصب
`/opt/pw-browsers/chromium` اشاره می‌کند، حالت بی‌پنجره و `--no-sandbox` را روشن می‌کند. آنجا هرگز
`playwright install` نزنید.

## راه‌اندازی یک‌باره

- **اولین نشست:** Claude Code برای سرورهای `.mcp.json` یک بار اجازه می‌خواهد؛ `.claude/settings.json`
  هر چهار را از پیش فعال می‌کند.
- **Snyk:** در [snyk.io](https://snyk.io) حساب رایگان بسازید و از Account settings توکن بگیرید. روی کامپیوتر
  خودتان `npx snyk@1.1307.4 auth` کافی است؛ برای نشست ابری `SNYK_TOKEN` را در متغیرهای محیط تنظیمات پروژه
  بگذارید. توکن هرگز در مخزن کامیت نمی‌شود.
- **شبکه نشست ابری:** `context7.com`، `mcp.context7.com`، `snyk.io` (با زیردامنه‌هایش مثل `api.snyk.io` و
  `static.snyk.io`) و برای آزمودن سایت زنده `farabehdasht.com` باید در دامنه‌های مجاز Network access باشند.
