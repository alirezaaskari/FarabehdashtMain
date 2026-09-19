#!/usr/bin/env bash
#
# گزارش وضعیت هاست — فقط می‌خواند، هیچ‌چیز را تغییر نمی‌دهد.
#
# روش اجرا:
#
#   الف) اگر مخزن روی سرور کلون شده:
#          bash scripts/host-check.sh
#
#   ب) اگر نشده: این فایل را با File Manager سی‌پنل در پوشه خانه آپلود کنید و
#      در Terminal بزنید:
#          bash ~/host-check.sh
#
#   ج) سریع‌ترین راه: کل محتوای این فایل را در Terminal سی‌پنل بچسبانید.
#      نسخه فشرده‌تری هم در docs/host-quick-check.md هست که یک‌جا چسباندنی است.
#
# مخزن خصوصی است، پس دانلود مستقیم از raw.githubusercontent بدون احراز هویت
# جواب نمی‌دهد و ۴۰۴ می‌گیرد.
#
# خروجی را کامل کپی کنید و بفرستید.

set -u

line() { printf '\n\033[1m== %s\033[0m\n' "$1"; }
ok()   { printf '  ✔ %s\n' "$1"; }
no()   { printf '  ✘ %s\n' "$1"; }
val()  { printf '  • %-28s %s\n' "$1" "$2"; }

# ---------------------------------------------------------------- PHP

line "PHP"

PHP_BIN=""
for candidate in /opt/cpanel/ea-php83/root/usr/bin/php /usr/local/bin/ea-php83 php83 php8.3 php; do
    if command -v "$candidate" >/dev/null 2>&1 || [ -x "$candidate" ]; then
        PHP_BIN="$candidate"
        break
    fi
done

if [ -z "$PHP_BIN" ]; then
    no "هیچ نسخه‌ای از PHP در PATH پیدا نشد"
else
    val "مسیر PHP" "$PHP_BIN"
    val "نسخه" "$("$PHP_BIN" -r 'echo PHP_VERSION;' 2>/dev/null)"

    line "افزونه‌های PHP"
    # intl برای تاریخ شمسی حیاتی است؛ بقیه برای لاراول و فایل لازم‌اند.
    for ext in intl mbstring bcmath zip fileinfo pdo_mysql openssl curl gd exif tokenizer xml ctype json; do
        if "$PHP_BIN" -m 2>/dev/null | grep -qix "$ext"; then
            ok "$ext"
        else
            no "$ext  ← غایب"
        fi
    done

    line "تنظیمات PHP"
    for setting in memory_limit max_execution_time upload_max_filesize post_max_size max_input_vars date.timezone; do
        val "$setting" "$("$PHP_BIN" -r "echo ini_get('$setting') ?: '(خالی)';" 2>/dev/null)"
    done

    line "توابعی که لاراول و Composer لازم دارند"
    for fn in proc_open proc_get_status symlink putenv exec shell_exec; do
        if "$PHP_BIN" -r "exit(function_exists('$fn') ? 0 : 1);" 2>/dev/null; then
            ok "$fn"
        else
            no "$fn  ← غیرفعال شده"
        fi
    done
fi

# ---------------------------------------------------------------- ابزارها

line "ابزارهای خط فرمان"

for tool in composer git node npm mysql unzip tar curl; do
    if command -v "$tool" >/dev/null 2>&1; then
        version="$("$tool" --version 2>/dev/null | head -n 1)"
        ok "$tool — ${version:-بدون شماره نسخه}"
    else
        no "$tool  ← نصب نیست"
    fi
done

# ---------------------------------------------------------------- فضا و اینود

line "فضا و اینود"

if command -v quota >/dev/null 2>&1; then
    quota -s 2>/dev/null | sed 's/^/  /' || no "quota جواب نداد"
else
    no "دستور quota در دسترس نیست — عدد را از نوار کناری سی‌پنل بخوانید"
fi

val "حجم پوشه خانه" "$(du -sh "$HOME" 2>/dev/null | cut -f1)"
val "تعداد فایل در خانه" "$(find "$HOME" -type f 2>/dev/null | wc -l)"

# ---------------------------------------------------------------- Cron

line "Cron"

if command -v crontab >/dev/null 2>&1; then
    ok "دستور crontab در دسترس است"
    printf '  cronهای فعلی:\n'
    crontab -l 2>/dev/null | sed 's/^/    /' || printf '    (هیچ cronی تعریف نشده)\n'
else
    no "crontab در دسترس نیست — از بخش Cron Jobs سی‌پنل بررسی کنید"
fi

# ---------------------------------------------------------------- ساختار سایت

line "ساختار پوشه‌ها"

val "پوشه خانه" "$HOME"

for dir in public_html public_html/cgi-bin; do
    if [ -d "$HOME/$dir" ]; then
        ok "$dir موجود است"
    else
        no "$dir موجود نیست"
    fi
done

# اگر Document Root قابل تغییر باشد، معمولاً هر دامنه پوشه جدا دارد.
if [ -f "$HOME/.cpanel/datastore/DOMAINS" ]; then
    printf '  دامنه‌ها:\n'
    sed 's/^/    /' "$HOME/.cpanel/datastore/DOMAINS" 2>/dev/null
fi

if [ -r "$HOME/public_html/.htaccess" ]; then
    ok ".htaccess خواندنی است — بازنویسی مسیر ممکن است"
fi

# ---------------------------------------------------------------- سرور

line "سرور"

val "نام سرور" "$(hostname 2>/dev/null)"
val "منطقه زمانی سیستم" "$(date +'%Z %z' 2>/dev/null)"
val "زمان محلی" "$(date 2>/dev/null)"
val "حافظه کل" "$(free -h 2>/dev/null | awk '/^Mem:/{print $2}' || echo 'نامشخص')"

printf '\n\033[1m== پایان گزارش ==\033[0m\n'
printf 'کل این خروجی را کپی کنید و بفرستید.\n\n'
