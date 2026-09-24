# استقرار فرابهداشت روی هاست

> **این راهنما برای سرور `h9.hostdl.com`، حساب `weeamore`، دامنه `farabehdasht.com` نوشته شده.**

## نسخه PHP

دامنه `farabehdasht.com` باید روی **PHP 8.4** باشد (سی‌پنل → MultiPHP Manager).
نسخه فعلی سرور: `8.4.25` در `/usr/local/bin/ea-php84`.

`composer.json` با `config.platform.php = 8.4.1` قفل شده، پس `composer.lock`
همیشه برای همین نسخه حل می‌شود — فارغ از اینکه چه کسی و با چه PHPای آن را
ساخته باشد.

> مجموعه افزونه‌های PHP در سی‌پنل **برای هر نسخه جداست**. اگر روزی نسخه را عوض
> کردید، `intl` و `bcmath` و بقیه را دوباره بررسی کنید.

---

## وضعیت فعلی دامنه — پیش از هر کاری بخوانید

`farabehdasht.com` همین الان یک **سایت وردپرسی زنده** را سرو می‌کند: قالب
اختصاصی `farabehdasht-child`، افزونه سئوی Rank Math و WooCommerce فعال.

مدیر تأیید کرده آن سایت **آزمایشی** است و محتوا یا سفارش واقعی ندارد، و تصمیم
گرفته سایت تازه جایگزینش شود (DEC-01، دوباره تأییدشده با دانستن این واقعیت).

با این حال:

- پوشه قدیمی **هرگز حذف نمی‌شود**، فقط کنار گذاشته می‌شود. برگرداندنش یک
  دستور `mv` است.
- **دیتابیس وردپرس دست نمی‌خورد.** ما دیتابیس تازه‌ای می‌سازیم؛ آن یکی سر جایش
  می‌ماند. تا چند هفته پاکش نکنید.
- جابه‌جایی چند ثانیه طول می‌کشد و همه کارهای سنگین (کلون، `composer install`،
  دارایی‌ها) **قبل** از آن انجام می‌شود، پس قطعی عملاً لحظه‌ای است.

---

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

### ۰٫۲ Document Root — ✅ تنظیم شد

در سی‌پنل روی `/home/weeamore/farabehdasht.com/public` تنظیم شد، یعنی
**چیدمان الف**: `.env`، `vendor` و کل کد برنامه بیرون از ریشه وب می‌مانند.

روش تأیید، بدون حدس:

```bash
echo "PUBLIC" > ~/farabehdasht.com/public/t.txt
curl -s https://farabehdasht.com/t.txt   # باید PUBLIC بدهد
rm -f ~/farabehdasht.com/public/t.txt
```

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

> **`DB_HOST` حتماً `localhost` باشد، نه `127.0.0.1`.** در MySQL این دو میزبانِ
> متفاوت‌اند: `localhost` از سوکت یونیکس می‌رود و `127.0.0.1` از TCP. سی‌پنل
> کاربر را به‌صورت `user@localhost` می‌سازد، پس `127.0.0.1` با این خطا رد می‌شود:
>
> ```
> SQLSTATE[HY000] [1130] Host '...' is not allowed to connect to this MariaDB server
> ```

> اگر رمز دیتابیس کاراکترهایی مثل `#`، `$`، `&` یا فاصله دارد، در `.env` داخل
> گیومه بگذاریدش: `DB_PASSWORD="رمز#شما"`. بدون گیومه، `#` بقیه خط را کامنت
> می‌کند و رمز ناقص خوانده می‌شود.

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

**کاری لازم نیست؛ با `git pull` می‌آیند.**

`npm` روی سرور نیست، پس `public/build` روی کامپیوتر ساخته می‌شود — ولی
**در مخزن کامیت می‌شود** و با همان `git pull` به سرور می‌رسد.

### چرا خروجی ساخت در گیت است

معمولاً خروجی ساخت را در گیت نمی‌گذارند، و دلیلشان درست است: نویز در diff و
حجم مخزن. این‌جا تصمیم عوض شد، چون هزینه طرف دیگر بیشتر بود.

وقتی `public/build` در gitignore بود، هر استقرار دو قدم داشت: `git pull` برای
کد، و یک آپلود دستی برای دارایی‌ها. اگر قدم دوم فراموش می‌شد، سایت **بالا
می‌آمد و کار می‌کرد** ولی قالب‌های تازه از کلاس‌هایی استفاده می‌کردند که در
CSS قدیمی نبودند: عنوان‌ها اندازه متن معمولی، محتوا چسبیده به لبه صفحه،
کارت‌ها بدون قاب. هیچ خطایی هم در هیچ لاگی ثبت نمی‌شد.

این یک‌بار واقعاً رخ داد. حالا یک قدم بیشتر نیست.

هزینه‌اش پذیرفته شده: ۱۶۴ کیلوبایت و شش فایل که در diff دیده می‌شوند.

### نگهبانش کجاست

در CI، نه در اسکریپت استقرار. گردش کار `npm run build` را اجرا می‌کند و اگر
نتیجه با آنچه کامیت شده فرق داشت، شکست می‌دهد. یعنی فراموش‌کردن `npm run
build` پیش از پوش، همان‌جا گرفته می‌شود و نه روی سایت زنده.

> **پس از هر تغییر در قالب یا CSS**، روی کامپیوتر `npm run build` بزنید و
> `public/build` را هم کامیت کنید — وگرنه CI قرمز می‌شود.

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
DB_HOST=localhost
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

# پیامک — فعلاً log تا وقتی اعتبار ملی‌پیامک را وارد کنید.
# در این حالت کد ورود به‌جای پیامک در storage/logs نوشته می‌شود:
#   grep 'کد ورود' storage/logs/*.log | tail -1
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
/usr/local/bin/ea-php84 artisan key:generate
```

---

## مرحله ۵ — جابه‌جایی پوشه به جای نهایی

**این کار قبل از اجرای اسکریپت استقرار انجام می‌شود، نه بعدش.** دلیلش
`storage:link` است: پیوندی که می‌سازد مسیر **مطلق** دارد، پس اگر پوشه بعدش
جابه‌جا شود، پیوند می‌شکند و هیچ فایل آپلودشده‌ای دیده نمی‌شود.

تا این لحظه پوشه قدیمی `~/farabehdasht.com` — یعنی سایت وردپرسی زنده —
دست‌نخورده مانده و سایت بالاست. از این لحظه سایت قدیمی می‌رود و تازه می‌آید.

```bash
cd ~
pwd   # باید /home/weeamore باشد

# سایت وردپرسی کنار گذاشته می‌شود، نه حذف. برگرداندنش یک mv است.
mv farabehdasht.com farabehdasht.com-wp-$(date +%Y%m%d)

mv farabehdasht.com-new farabehdasht.com

ls -d farabehdasht.com*   # هر دو باید دیده شوند
```

> **برگشت فوری، اگر چیزی خراب شد:**
> ```bash
> cd ~
> mv farabehdasht.com farabehdasht.com-new
> mv farabehdasht.com-wp-* farabehdasht.com
> ```
> و اگر Document Root را عوض کرده‌اید، به حالت قبل برگردانید. سایت وردپرسی
> ظرف چند ثانیه برمی‌گردد.

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
for d in app bootstrap config database packages resources routes storage tests vendor docs scripts; do
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
/usr/local/bin/ea-php84 /home/weeamore/farabehdasht.com/artisan schedule:run >> /dev/null 2>&1
```

این یک ردیف کافی است؛ زمان‌بند لاراول بقیه کارها را خودش مدیریت می‌کند.

---

## مرحله ۹ — حساب مدیر

```bash
cd ~/farabehdasht.com
/usr/local/bin/ea-php84 artisan fbh:make-admin 09xxxxxxxxx
```

شماره موبایل خودتان را بگذارید. ورود با همان شماره و کد یک‌بارمصرف است.

> تا وقتی `FBH_SMS_DRIVER=log` است، کد به‌جای پیامک در لاگ نوشته می‌شود.
> برای اولین ورود:
>
> ```bash
> grep -o 'کد ورود شما به فرابهداشت: [0-9]*' storage/logs/*.log | tail -1
> ```
>
> درایور `log` در سطح **warning** می‌نویسد تا با `LOG_LEVEL=warning` هم دیده
> شود. سطح `info` این پیام را بی‌صدا دور می‌ریخت و کاربر هیچ راهی برای ورود
> نداشت.

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

## چک‌لیست انتشار — خروج از staging

پایان بخش ۱۷ یعنی «آماده انتشار»، نه «منتشرشده» (DEC-36). این مرحله تصمیم جدا و
صریح مدیر است، پس از خواندن `docs/acceptance/v1/README.md` و انجام
`docs/acceptance/v1/manual-checklist.md`. هیچ‌کدام از این بندها را کد یا دستیار
انجام نمی‌دهد.

پیش از تغییر:

- [ ] `composer run check` روی آخرین `main` در CI سبز است (شامل بررسی دسترس‌پذیری و صفحه‌کلید).
- [ ] پرداخت واقعی ۱۰٬۰۰۰ تومانی و بازگشتش انجام و ثبت شده (بند ۱ چک‌لیست دستی).
- [ ] نسخه ۱ «شرایط استفاده» و «حریم خصوصی» از صفحه «اسناد حقوقی» پنل منتشر شده.
- [ ] Cron زمان‌بند (مرحله ۸) فعال است و HOST-05 و HOST-06 در `docs/decisions-pending.md` بسته شده‌اند.
- [ ] پشتیبان تازه از دیتابیس گرفته شده.

تغییر `.env` هاست:

```dotenv
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=warning
FBH_SMS_DRIVER=melipayamak      # با MELIPAYAMAK_* واقعی؛ در log کد ورود به کسی نمی‌رسد
FBH_PAYMENT_DRIVER=zarinpal
ZARINPAL_SANDBOX=false
FBH_ADMIN_PATH=...              # مسیر پنل، غیرقابل حدس
```

سپس `bash scripts/deploy.sh` (کش تنظیمات را از نو می‌سازد).

پس از تغییر:

- [ ] `https://farabehdasht.com/robots.txt` دیگر `Disallow: /` نیست و خط `Sitemap:` دارد.
- [ ] `/design-system` ۴۰۴ می‌دهد.
- [ ] یک ورود واقعی با پیامک.
- [ ] یک صفحه خطا (مثلاً نشانی نادرست) جزئیات فنی نشان نمی‌دهد.
- [ ] نقشه سایت در Google Search Console ثبت شده (`/sitemap.xml`).
- [ ] Rich Results Test روی یک صفحه از هر نوع (بند ۶ چک‌لیست دستی).

برگشت: `APP_ENV=staging` و یک `bash scripts/deploy.sh` دیگر؛ `robots.txt` دوباره
همه را می‌بندد و هیچ داده‌ای عوض نمی‌شود.

---

## بعد از موفقیت — پاک‌سازی

اینها را **زود انجام ندهید**. دست‌کم چند هفته صبر کنید:

- پوشه `~/farabehdasht.com-wp-*` را نگه دارید.
- دیتابیس وردپرس را در سی‌پنل نگه دارید.
- توکن GitHub را همین حالا **Revoke** کنید — این یکی فوری است.

---

## به‌روزرسانی‌های بعدی

```bash
cd ~/farabehdasht.com
git pull origin main
bash scripts/deploy.sh
```

> **هرگز ZIP گیت‌هاب را روی سایت آپلود نکنید.** فقط `git pull`. دلیلش پایین است.

---

## تله `.htaccess` و نسخه PHP

سی‌پنل نسخه PHP هر دامنه را با یک خط `AddHandler` داخل **`.htaccess` همان
Document Root** تنظیم می‌کند — این‌جا یعنی `~/farabehdasht.com/public/.htaccess`.

آن فایل در گیت **ردیابی می‌شود** و نسخه مخزن (استاندارد Laravel) این خط را
ندارد. پس هر چیزی که رویش بنویسد، بی‌صدا سایت را به PHP پیش‌فرض حساب
برمی‌گرداند:

```
Composer detected issues in your platform:
Your Composer dependencies require a PHP version ">= 8.4.1".
```

این اتفاق یک بار افتاد، بعد از آپلود ZIP گیت‌هاب از فایل‌منیجر.

**اصلاح (به همین ترتیب — اول گیت، بعد سی‌پنل):**

```bash
cd ~/farabehdasht.com
git config remote.origin.fetch '+refs/heads/*:refs/remotes/origin/*'
git fetch origin
git checkout -f -B main origin/main
git branch --set-upstream-to=origin/main main
```

سپس سی‌پنل ← **MultiPHP Manager** ← دامنه ← `ea-php84` ← **Apply**، و بررسی:

```bash
grep -i AddHandler ~/farabehdasht.com/public/.htaccess
```

**و یک بار برای همیشه:**

```bash
git update-index --skip-worktree public/.htaccess
```

از این پس `git pull` و `git reset` به این فایل کار ندارند.

> ترتیب اهمیت دارد: اگر اول MultiPHP را بزنید و بعد `git checkout` کنید، گیت
> دوباره خط را پاک می‌کند.

`scripts/deploy.sh` حالا نبودِ این خط را تشخیص می‌دهد و هشدار می‌دهد — هشدار و
نه خطا، چون میزبان‌های دیگر چنین چیزی ندارند.

---

## چرا آپلود ZIP به‌جای `git pull` بد است

ZIP گیت‌هاب این چهار چیز را **ندارد** و آپلودش می‌تواند خرابشان کند:

| چیز | چرا در ZIP نیست | اگر پاک شود |
|---|---|---|
| `.env` | در gitignore | سایت بالا نمی‌آید (بدون `APP_KEY`) |
| `vendor/` | در gitignore | `composer install` لازم می‌شود |
| `public/build/` | هست، ولی ZIP آن را با نسخه لحظه دانلود جایگزین می‌کند | استایل با قالب‌ها نمی‌خواند |
| `.git/` | بخشی از ZIP نیست | `git pull` بعدی کار نمی‌کند |

به‌علاوه `public/.htaccess` را بازنویسی می‌کند و تله بالا را می‌سازد.

همچنین Extract کردن ZIP یک پوشه تودرتو مثل `FarabehdashtMain-main` می‌سازد که
اگر پاک نشود، اینود مصرف می‌کند.

---

## عیب‌یابی سریع

| نشانه | علت محتمل |
| --- | --- |
| صفحه سفید یا خطای ۵۰۰ | `storage/logs/laravel.log` را بخوانید؛ معمولاً دسترسی پوشه یا `.env` ناقص |
| سایت بدون استایل | `public/build` روی سرور نیست — `git pull` کامل انجام نشده |
| سایت بالا می‌آید ولی بهم‌ریخته است | CSS قدیمی است: کلاس‌های قالب تازه در آن نیستند. روی کامپیوتر `npm run build`، کامیت، پوش، و روی سرور `git pull` |
| «۴۰۴» روی همه صفحات | Document Root یا `.htaccess` درست نیست |
| پنل ۴۰۳ می‌دهد | حساب شما نقش مدیریتی ندارد؛ `fbh:make-admin` را اجرا کنید |
| پیامک نمی‌رسد | `FBH_SMS_DRIVER` هنوز `log` است، یا API ملی‌پیامک آی‌پی سرور خارجی را رد می‌کند |
| کد ورود در لاگ نیست | `php artisan config:cache` را بعد از تغییر `.env` دوباره اجرا کنید |
| خطای ۱۱۳۰ دیتابیس | `DB_HOST` روی `127.0.0.1` است؛ باید `localhost` باشد |
| `Composer detected issues in your platform` | خط `AddHandler` از `public/.htaccess` پاک شده — بخش «تله `.htaccess` و نسخه PHP» |
| بعد از تغییر `.env` هیچ اثری نیست | `artisan config:cache` را دوباره اجرا کنید |

> هر خطا را با **متن کامل** بفرستید، نه خلاصه‌اش. پیام خطای لاراول معمولاً
> دقیقاً می‌گوید کجا را نگاه کنیم.
