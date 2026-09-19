# بررسی سریع هاست

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
echo "--- dirs"; ls -d ~/public_html 2>/dev/null || echo "  no public_html"; echo "  home=$HOME"
echo "--- server"; hostname; date +'%Z %z'; df -h ~ | tail -1
```

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
