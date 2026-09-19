# بررسی سریع هاست

> ⚠️ **روی سرور درست اجرا کنید.** هاست فرابهداشت روی سرور **`h9`** است. اگر
> چند حساب سی‌پنل دارید، اول با `hostname` مطمئن شوید که در همان حساب هستید؛
> خروجی گرفته‌شده از سرور دیگر به‌کار نمی‌آید.

مخزن خصوصی است، پس `curl` مستقیم از GitHub بدون احراز هویت ۴۰۴ می‌گیرد.
ساده‌ترین راه: کل بلوک زیر را در **Terminal سی‌پنل** بچسبانید و Enter بزنید.

فقط می‌خواند؛ هیچ فایلی نمی‌سازد و هیچ تنظیمی را عوض نمی‌کند.

```bash
P=$(command -v ea-php83 || command -v php83 || command -v php); echo "PHP: $P -> $($P -r 'echo PHP_VERSION;')"
echo "--- extensions"; for e in intl mbstring bcmath zip fileinfo pdo_mysql openssl curl gd exif; do $P -m | grep -qix $e && echo "  OK  $e" || echo "  MISSING  $e"; done
echo "--- ini"; for k in memory_limit max_execution_time upload_max_filesize post_max_size; do echo "  $k = $($P -r "echo ini_get('$k');")"; done
echo "--- functions"; for f in proc_open symlink putenv exec; do $P -r "exit(function_exists('$f')?0:1);" && echo "  OK  $f" || echo "  DISABLED  $f"; done
echo "--- tools"; for t in composer git node npm mysql unzip; do command -v $t >/dev/null && echo "  OK  $t $($t --version 2>/dev/null|head -1)" || echo "  MISSING  $t"; done
echo "--- quota"; quota -s 2>/dev/null || echo "  (no quota cmd)"
echo "--- cron"; crontab -l 2>/dev/null; command -v crontab >/dev/null && echo "  (crontab works)" || echo "  (no crontab)"
echo "--- dirs"; echo "  home=$HOME"; ls -d ~/public_html ~/farabehdasht.com ~/farabehdasht.com/public 2>/dev/null; ls -1 ~ | head -20
echo "--- server"; hostname; date +'%Z %z'; df -h ~ | tail -1
echo "--- LVE (CloudLinux)"; lvectl limits 2>/dev/null || cat /proc/self/cagefs 2>/dev/null || echo "  (limits from cPanel > Resource Usage)"
```

خط اول خروجی باید `h9…` باشد. اگر چیز دیگری بود، روی حساب اشتباهی هستید.

## چه چیزی را می‌بندد

| خروجی | پرسش باز |
|---|---|
| `extensions` | HOST-08 — بدون `intl` تاریخ شمسی کار نمی‌کند |
| `quota` و `df` | HOST-06 — سقف فضا و اینود |
| `cron` | HOST-05 — امکان زمان‌بندی |
| `ini` و `functions` | اینکه `composer install` و `artisan migrate` واقعاً اجرا می‌شوند |
| `tools` | نبودن `node` و `npm` تأیید می‌کند که `public/build` باید روی کامپیوتر ساخته شود |

دو مورد از این بلوک درنمی‌آید:

- **HOST-04** — Document Root: سی‌پنل → `Domains` → ستون Document Root
- **HOST-07** — سرور ایران است یا خارج: از پشتیبانی هاست بپرسید

---

## تست اتصال شبکه (HOST-09)

سرور `h9` روی آی‌پی `49.12.129.169` است که در بازه Hetzner (آلمان) قرار دارد،
پس احتمال رسیدن به GitHub زیاد است. ولی `composer install` آرشیو بسته‌ها را از
GitHub می‌گیرد و این را باید **پیش از** نوشتن روند استقرار قطعی کنیم، نه حدس.

این بلوک را در Terminal بچسبانید:

```bash
for u in https://repo.packagist.org/packages.json \
         https://codeload.github.com/laravel/framework/tar.gz/refs/tags/v12.0.0 \
         https://api.github.com/ \
         https://github.com/ ; do
  printf '%-70s %s\n' "$u" "$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 "$u")"
done
```

خواندن نتیجه:

| کد | معنی |
|---|---|
| `200` | می‌رسد — استقرار عادی است |
| `403` | رد شده — یعنی `vendor` باید روی کامپیوتر ساخته و منتقل شود |
| `000` | اصلاً وصل نشد (فیلتر یا تایم‌اوت) — همان نتیجه ۴۰۳ |

اگر `codeload.github.com` پاسخ ۴۰۳ یا ۰۰۰ بدهد ولی `repo.packagist.org` جواب
۲۰۰ بدهد، یک راه میانی هم هست: تنظیم Composer روی نصب از source به‌جای dist.
