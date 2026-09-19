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

PHP="${FBH_PHP:-/usr/local/bin/ea-php83}"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

step()  { printf '\n\033[1m▸ %s\033[0m\n' "$1"; }
ok()    { printf '  ✔ %s\n' "$1"; }
fail()  { printf '  ✘ %s\n' "$1" >&2; exit 1; }

cd "$ROOT"

# ---------------------------------------------------------------- پیش‌بررسی

step "بررسی پیش‌نیازها"

[ -x "$PHP" ] || fail "PHP در $PHP پیدا نشد. با FBH_PHP مسیر درست را بدهید."
ok "PHP $("$PHP" -r 'echo PHP_VERSION;')"

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
    fail "public/build وجود ندارد. دارایی‌ها روی کامپیوتر ساخته و منتقل می‌شوند (npm روی سرور نیست)."
fi
ok "دارایی‌های ساخته‌شده موجودند"

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
