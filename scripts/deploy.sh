#!/usr/bin/env bash
#
# استقرار فرابهداشت روی هاست اشتراکی.
#
# این اسکریپت را در Terminal سی‌پنل، از داخل پوشه برنامه اجرا کنید:
#
#   cd ~/farabehdasht.com && bash scripts/deploy.sh
#
# چه کاری می‌کند: وابستگی‌ها را نصب، مهاجرت‌ها را اجرا، دارایی‌های Filament را
# منتشر و کش‌ها را تازه می‌کند. هر بار بعد از گرفتن نسخه تازه کد، همین اجرا شود.
#
# چه کاری نمی‌کند: چیزی بیرون از پوشه برنامه را دست نمی‌زند. این حساب چند سایت
# زنده دارد و یک دستور اشتباه در پوشه خانه، همه‌شان را از کار می‌اندازد.

set -euo pipefail

PHP="${FBH_PHP:-/usr/local/bin/ea-php84}"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

step()  { printf '\n\033[1m▸ %s\033[0m\n' "$1"; }
ok()    { printf '  ✔ %s\n' "$1"; }
fail()  { printf '  ✘ %s\n' "$1" >&2; exit 1; }

cd "$ROOT"

# ---------------------------------------------------------------- پیش‌بررسی

step "بررسی پیش‌نیازها"

[ -x "$PHP" ] || fail "PHP در $PHP پیدا نشد. با FBH_PHP مسیر درست را بدهید."

PHP_VERSION="$("$PHP" -r 'echo PHP_VERSION;')"
ok "PHP $PHP_VERSION"

# نسخه باید با config.platform.php در composer.json بخواند، وگرنه نصب وسط کار
# با پیام گیج‌کننده «lock file does not contain a compatible set» می‌ایستد.
case "$PHP_VERSION" in
    8.4.*) ;;
    *) fail "این پروژه به PHP 8.4 نیاز دارد؛ نسخه فعلی $PHP_VERSION است. در سی‌پنل → MultiPHP Manager عوضش کنید." ;;
esac

[ -f artisan ] || fail "این پوشه ریشه برنامه نیست؛ فایل artisan پیدا نشد."

[ -f .env ] || fail ".env وجود ندارد. اول آن را بسازید (راهنما: docs/deployment.md)."
ok ".env موجود است"

grep -q '^APP_KEY=base64:' .env || fail "APP_KEY خالی است. اجرا کنید: $PHP artisan key:generate"
ok "APP_KEY تنظیم شده"

if ! grep -q '^APP_ENV=production' .env && ! grep -q '^APP_ENV=staging' .env; then
    printf '  ⚠ APP_ENV نه production است و نه staging.\n'
fi

command -v composer >/dev/null || fail "composer روی سرور پیدا نشد."

if [ ! -d public/build ]; then
    fail "public/build وجود ندارد. دارایی‌ها روی کامپیوتر ساخته و با گیت منتقل می‌شوند (npm روی سرور نیست)."
fi

ok "دارایی‌های ساخته‌شده موجودند"

# سی‌پنل نسخه PHP هر دامنه را با یک خط AddHandler داخل .htaccess همان
# Document Root تنظیم می‌کند. آن فایل در گیت ردیابی می‌شود و نسخه مخزن این
# خط را ندارد، پس هر بازنویسی‌اش (git checkout، آپلود ZIP) سایت را بی‌صدا به
# PHP پیش‌فرض حساب برمی‌گرداند و نتیجه‌اش این خطاست:
#
#   Composer detected issues in your platform: ... require a PHP version ">= 8.4.1"
#
# این‌جا فقط هشدار است و نه خطا، چون میزبان‌های دیگر چنین چیزی ندارند.
if [ -f public/.htaccess ] && ! grep -qi 'AddHandler' public/.htaccess; then
    printf '  ⚠ خط AddHandler در public/.htaccess نیست.\n'
    printf '    اگر روی سی‌پنل هستید، سایت با PHP پیش‌فرض حساب اجرا می‌شود، نه ۸٫۴.\n'
    printf '    اصلاح: MultiPHP Manager ← دامنه ← ea-php84 ← Apply\n'
    printf '    و بعد یک بار: git update-index --skip-worktree public/.htaccess\n'
fi

# ---------------------------------------------------------------- وابستگی‌ها

step "نصب وابستگی‌های PHP"

"$PHP" "$(command -v composer)" install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

ok "وابستگی‌ها نصب شدند"

# ---------------------------------------------------------------- دیتابیس

step "مهاجرت دیتابیس"

"$PHP" artisan migrate --force
ok "مهاجرت‌ها اجرا شدند"

# ابزار تازه‌ای که در کد اضافه شده باید ردیف وضعیت بگیرد. ردیف موجود دست
# نمی‌خورد، پس ابزاری که مدیر خاموش کرده با استقرار دوباره روشن نمی‌شود.
step "همگام‌سازی فهرست ابزارها"

"$PHP" artisan fbh:sync-tools
ok "فهرست ابزارها همگام شد"

# ---------------------------------------------------------------- دارایی‌ها

step "دارایی‌ها و پیوند ذخیره‌سازی"

"$PHP" artisan filament:assets
ok "دارایی‌های Filament منتشر شدند"

if [ ! -L public/storage ]; then
    "$PHP" artisan storage:link
    ok "پیوند public/storage ساخته شد"
else
    ok "پیوند public/storage از قبل هست"
fi

# ---------------------------------------------------------------- کش

step "تازه‌سازی کش"

"$PHP" artisan config:clear
"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:cache
"$PHP" artisan event:cache
ok "کش‌ها ساخته شدند"

# پیوندهای داخلی از روی متن فعلی دوباره ساخته می‌شوند: محتوا و مواد با
# مهاجرت یا دستور ورود عوض می‌شوند، نه از پنل. بعد از کش، تا نشانی‌ها از
# APP_URL نهایی ساخته شوند. اگر ماژول پیوند خاموش باشد، این مرحله رد می‌شود.
step "بازسازی پیوندهای داخلی"

if "$PHP" artisan list --raw | grep -q '^fbh:links:rebuild'; then
    "$PHP" artisan fbh:links:rebuild
    ok "پیوندهای داخلی بازسازی شدند"
else
    ok "ماژول پیوند خاموش است؛ رد شد"
fi

# ---------------------------------------------------------------- دسترسی‌ها

step "دسترسی پوشه‌ها"

chmod -R u+rwX,go-w storage bootstrap/cache
ok "storage و bootstrap/cache قابل نوشتن‌اند"

# ---------------------------------------------------------------- بررسی نهایی

step "بررسی نهایی"

"$PHP" artisan about --only=environment 2>/dev/null | sed 's/^/  /' || true

printf '\n\033[1m✔ استقرار تمام شد.\033[0m\n'
printf 'اگر اولین بار است، حساب مدیر را بسازید:\n'
printf '  %s artisan fbh:make-admin 09xxxxxxxxx\n\n' "$PHP"
