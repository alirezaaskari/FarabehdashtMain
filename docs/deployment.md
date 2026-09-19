# استقرار فرابهداشت روی هاست

> **این راهنما برای سرور `h9.hostdl.com`، حساب `weeamore`، دامنه `farabehdasht.com` نوشته شده.**

## قاعده‌ای که هرگز نقض نمی‌شود

این حساب سی‌پنل **چند سایت زنده** دارد:

```
/home/weeamore/
├─ public_html/          ← سایت دامنه اصلی (وردپرس زنده)
├─ talkiland.com/
├─ goldenkasht.com/
├─ drhonarvar.ir/
└─ farabehdasht.com/     ← فقط این پوشه مال ماست
```

**هیچ دستوری بیرون از `~/farabehdasht.com` اجرا نمی‌شود.** نه `rm` در پوشه خانه،
نه دست‌زدن به `public_html`، نه تغییر فایل‌های وردپرس. یک اشتباه، چهار سایت را
با هم از کار می‌اندازد.

قبل از هر دستور مخرب، یک بار `pwd` بزنید و مطمئن شوید در پوشه درستید.

---

## مرحله ۰ — دو چیزی که باید اول بدانیم

### ۰٫۱ اتصال به packagist و GitHub — ✅ بررسی شد

تست شبکه روی `h9` هر سه نشانی را `200` برگرداند. یعنی **مسیر الف**:
`composer install` روی خود سرور اجرا می‌شود و نیازی به انتقال پوشه `vendor`
نیست.

### ۰٫۲ آیا Document Root قابل تغییر است؟

سی‌پنل → `Domains` → کنار `farabehdasht.com` → آیا ستون **Document Root** قابل
ویرایش است؟

| نتیجه | یعنی |
| --- | --- |
| بله | **چیدمان الف** — امن‌ترین حالت |
| نه | **چیدمان ب** — با `.htaccess` حل می‌شود |

---

## مرحله ۱ — دیتابیس

سی‌پنل → **MySQL® Databases**:

1. **Create New Database:** نامی مثل `farabeh` بسازید. سی‌پنل خودش پیشوند
   می‌زند و نام نهایی چیزی مثل `weeamore_farabeh` می‌شود.
2. **Add New User:** کاربری مثل `farabeh` با **رمز قوی و تصادفی** بسازید.
   رمز را همان‌جا کپی کنید؛ بعداً دیده نمی‌شود.
3. **Add User To Database:** کاربر را به دیتابیس اضافه کنید با **ALL PRIVILEGES**.

نام کامل دیتابیس، نام کامل کاربر و رمز را نگه دارید.

> کاربر دیتابیس این سایت باید **مخصوص همین سایت** باشد. کاربر مشترک با
> سایت‌های دیگر یعنی یک نفوذ، به همه‌شان می‌رسد.

---

## مرحله ۲ — بردن کد روی سرور

### مسیر الف — با git ✅

مخزن خصوصی است، پس برای کلون به یک توکن نیاز است.

1. در GitHub → `Settings` → `Developer settings` → `Personal access tokens` →
   **Fine-grained tokens** → `Generate new token`.
2. دسترسی: فقط مخزن `FarabehdashtMain`، و فقط **Contents: Read-only**.
3. انقضا: کوتاه بگذارید (مثلاً ۷ روز). فقط برای همین کلون لازم است.

در Terminal:

```bash
cd ~
git clone --branch claude/pensive-brahmagupta-p5qa0o \
  https://<TOKEN>@github.com/alirezaaskari/FarabehdashtMain.git farabehdasht.com-new

# توکن را فوراً از تنظیمات مخزن پاک کنید تا در .git/config نماند
cd ~/farabehdasht.com-new
git remote set-url origin https://github.com/alirezaaskari/FarabehdashtMain.git
```

> توکن را بعد از استقرار در GitHub **Revoke** کنید. توکن روی هاست اشتراکی
> نباید بماند.

### مسیر ب — با آپلود فایل (لازم نشد)

این مسیر برای حالتی بود که سرور به GitHub نرسد. تست شبکه نشان داد می‌رسد، پس
همان مسیر الف را جلو می‌رویم. مسیر ب اینجا می‌ماند برای روزی که لازم شود.

---

## مرحله ۳ — دارایی‌های ساخته‌شده

`npm` روی سرور نیست، پس `public/build` روی کامپیوتر ساخته و منتقل می‌شود.

**لازم نیست خودتان چیزی نصب کنید — من بسته آماده را می‌فرستم.** فایل
`farabehdasht-assets.tar.gz` (حدود ۱۱۰ کیلوبایت) را بگیرید و:

1. با **File Manager** سی‌پنل در `~/farabehdasht.com-new/public/` آپلودش کنید.
2. همان‌جا راست‌کلیک → **Extract**.
3. باید پوشه `public/build/` ساخته شود با `manifest.json` داخلش.

یا در Terminal، اگر فایل را در پوشه خانه گذاشتید:

```bash
cd ~/farabehdasht.com-new/public
tar -xzf ~/farabehdasht-assets.tar.gz
ls build/manifest.json   # باید وجود داشته باشد
```

> اگر خودتان Node دارید و ترجیح می‌دهید بسازید: `npm ci && npm run build`
> و پوشه `public/build` حاصل را منتقل کنید. نتیجه یکی است.

> بدون این پوشه، سایت بالا می‌آید ولی **هیچ استایلی ندارد**. اسکریپت استقرار
> هم نبودنش را می‌گیرد و خطا می‌دهد.

---

## مرحله ۴ — فایل `.env`

در `~/farabehdasht.com-new` فایلی به نام `.env` بسازید:

```dotenv
APP_NAME=فرابهداشت
APP_ENV=staging
APP_KEY=
APP_DEBUG=false
APP_URL=https://farabehdasht.com

APP_LOCALE=fa
APP_FALLBACK_LOCALE=fa
APP_TIMEZONE=Asia/Tehran

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=weeamore_farabeh
DB_USERNAME=weeamore_farabeh
DB_PASSWORD=رمزی-که-در-مرحله-۱-ساختید

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

CACHE_STORE=database
QUEUE_CONNECTION=database

# پنل مدیریت — این مسیر را عوض کنید و جایی امن نگه دارید
FBH_ADMIN_PATH=fbh-panel

# پیامک — فعلاً log تا وقتی اعتبار ملی‌پیامک را وارد کنید
FBH_SMS_DRIVER=log
MELIPAYAMAK_USERNAME=
MELIPAYAMAK_PASSWORD=
MELIPAYAMAK_FROM=
```

سه نکته:

- **`APP_ENV=staging` عمدی است.** تا وقتی محتوایی نداریم، `robots.txt` کل سایت
  را می‌بندد و گوگل چیزی نمی‌بیند. وقتی آماده شدیم `production` می‌شود.
- **`APP_DEBUG=false` حتی در staging.** صفحه خطای لاراول مسیر فایل‌ها و بخشی از
  پیکربندی را نشان می‌دهد.
- **`FBH_ADMIN_PATH` را عوض کنید.** پیش‌فرض در مخزن نوشته شده، پس دیگر
  غیرقابل‌حدس نیست.

بعد کلید برنامه را بسازید:

```bash
cd ~/farabehdasht.com-new
/usr/local/bin/ea-php83 artisan key:generate
```

---

## مرحله ۵ — جابه‌جایی پوشه به جای نهایی

**این کار قبل از اجرای اسکریپت استقرار انجام می‌شود، نه بعدش.** دلیلش
`storage:link` است: پیوندی که می‌سازد مسیر **مطلق** دارد، پس اگر پوشه بعدش
جابه‌جا شود، پیوند می‌شکند و هیچ فایل آپلودشده‌ای دیده نمی‌شود.

تا این لحظه پوشه قدیمی `~/farabehdasht.com` دست‌نخورده مانده. حالا:

```bash
cd ~
pwd   # باید /home/weeamore باشد

# اگر پوشه قدیمی محتوایی دارد، اول کنار بگذاریدش — حذفش نکنید
[ -d farabehdasht.com ] && mv farabehdasht.com farabehdasht.com-old-$(date +%Y%m%d)

mv farabehdasht.com-new farabehdasht.com
```

### چیدمان الف — Document Root قابل تغییر است

سی‌پنل → `Domains` → `farabehdasht.com` → Document Root را به:

```
/home/weeamore/farabehdasht.com/public
```

تغییر دهید. تمام. این امن‌ترین حالت است: `.env`، `vendor` و کد برنامه بیرون از
دسترس وب می‌مانند.

### چیدمان ب — Document Root قابل تغییر نیست

فایل `~/farabehdasht.com/.htaccess` را با این محتوا بسازید:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # هر درخواستی به پوشه public هدایت می‌شود
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>

# فایل‌هایی که هرگز نباید از وب خوانده شوند
<FilesMatch "^(\.env.*|composer\.(json|lock)|package(-lock)?\.json|artisan)$">
    Require all denied
</FilesMatch>
```

و دسترسی مستقیم به پوشه‌های حساس را ببندید:

```bash
cd ~/farabehdasht.com
for d in app bootstrap config database resources routes storage tests vendor docs scripts; do
  printf 'Require all denied\n' > "$d/.htaccess"
done
```

> این چیدمان از الف ضعیف‌تر است، چون کد برنامه زیر ریشه وب قرار می‌گیرد و
> امنیتش به درست‌کارکردن `.htaccess` وابسته است. اگر شد، الف را انتخاب کنید.

---

## مرحله ۶ — استقرار

حالا که پوشه سر جای نهایی‌اش است:

```bash
cd ~/farabehdasht.com
pwd   # باید /home/weeamore/farabehdasht.com باشد
bash scripts/deploy.sh
```

اسکریپت وابستگی‌ها را نصب، مهاجرت‌ها را اجرا، دارایی‌های Filament را منتشر،
پیوند ذخیره‌سازی را بسازد و کش‌ها را تازه می‌کند. هر بار بعد از گرفتن نسخه تازه کد، همین اجرا می‌شود.

> **اگر `composer install` به‌خاطر حافظه شکست:** CloudLinux سقف حافظه
> per-account دارد. امتحان کنید:
> `COMPOSER_MEMORY_LIMIT=-1 bash scripts/deploy.sh`
> و اگر باز هم نشد، عدد سقف را از سی‌پنل → `Resource Usage` بخوانید و بگویید.

---

## مرحله ۷ — SSL

سی‌پنل → **SSL/TLS Status** → `farabehdasht.com` را انتخاب و
**Run AutoSSL** بزنید. چند دقیقه طول می‌کشد.

بعد اجبار به HTTPS را در همان `.htaccess` ریشه، **بالای** قاعده بازنویسی اضافه کنید:

```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## مرحله ۸ — Cron

سی‌پنل → **Cron Jobs** → یک ردیف اضافه کنید:

- بازه: **هر دقیقه** (اگر اجازه نداد، هر پنج دقیقه هم کافی است)
- دستور:

```
/usr/local/bin/ea-php83 /home/weeamore/farabehdasht.com/artisan schedule:run >> /dev/null 2>&1
```

این یک ردیف کافی است؛ زمان‌بند لاراول بقیه کارها را خودش مدیریت می‌کند.

---

## مرحله ۹ — حساب مدیر

```bash
cd ~/farabehdasht.com
/usr/local/bin/ea-php83 artisan fbh:make-admin 09xxxxxxxxx
```

شماره موبایل خودتان را بگذارید. ورود با همان شماره و کد یک‌بارمصرف است.

> تا وقتی `FBH_SMS_DRIVER=log` است، کد در `storage/logs/laravel.log` نوشته
> می‌شود. برای اولین ورود همان را بخوانید:
> `tail -n 5 storage/logs/laravel.log`

---

## مرحله ۱۰ — بررسی

| آدرس | باید چه ببینید |
| --- | --- |
| `https://farabehdasht.com/` | صفحه موقت خانه |
| `https://farabehdasht.com/login` | صفحه ورود با موبایل |
| `https://farabehdasht.com/robots.txt` | `Disallow: /` (چون staging است) |
| `https://farabehdasht.com/sitemap.xml` | XML معتبر و فعلاً خالی |
| `https://farabehdasht.com/یک-نشانی-نادرست` | صفحه ۴۰۴ فارسی |
| `https://farabehdasht.com/fbh-panel` | پنل مدیریت، بعد از ورود |

اگر هر کدام آن چیزی نبود که باید، **متن کامل خطا** را بفرستید.

> **یک نکته برای بعد:** صفحه `/design-system` در حالت staging باز است و فقط در
> production بسته می‌شود. ابزار داخلی تیم است و داده‌ای ندارد، و `robots.txt`
> هم فعلاً کل سایت را بسته — ولی پیش از رفتن به production باید یادمان باشد.

---

## به‌روزرسانی‌های بعدی

```bash
cd ~/farabehdasht.com
git pull origin claude/pensive-brahmagupta-p5qa0o
# پوشه public/build تازه را هم منتقل کنید
bash scripts/deploy.sh
```

---

## عیب‌یابی سریع

| نشانه | علت محتمل |
| --- | --- |
| صفحه سفید یا خطای ۵۰۰ | `storage/logs/laravel.log` را بخوانید؛ معمولاً دسترسی پوشه یا `.env` ناقص |
| سایت بدون استایل | `public/build` منتقل نشده |
| «۴۰۴» روی همه صفحات | Document Root یا `.htaccess` درست نیست |
| پنل ۴۰۳ می‌دهد | حساب شما نقش مدیریتی ندارد؛ `fbh:make-admin` را اجرا کنید |
| پیامک نمی‌رسد | `FBH_SMS_DRIVER` هنوز `log` است، یا API ملی‌پیامک آی‌پی سرور خارجی را رد می‌کند |
| بعد از تغییر `.env` هیچ اثری نیست | `artisan config:cache` را دوباره اجرا کنید |

> هر خطا را با **متن کامل** بفرستید، نه خلاصه‌اش. پیام خطای لاراول معمولاً
> دقیقاً می‌گوید کجا را نگاه کنیم.
