<?php

declare(strict_types=1);

namespace App\Support\Help\Motion;

use InvalidArgumentException;

/**
 * فهرست آموزش‌های متحرک، به کلید راهنمای بخش (config/help.php).
 *
 * هر ماژول آموزش خودش را در ServiceProvider ثبت می‌کند؛ ماژول خاموش یعنی
 * آموزشی ثبت نشده و راهنمای متنی تنها می‌ماند. x-page-help هر آموزشی را که
 * برای کلیدش ثبت شده باشد بالای متن راهنما نشان می‌دهد.
 *
 * فایل تعریف یک آرایه PHP برمی‌گرداند:
 *
 *   title    عنوان آموزش (برچسب دسترس‌پذیری)
 *   url      نشانی نوار بالای پنجره؛ هر صحنه می‌تواند url خودش را داشته باشد
 *   steps    برچسب‌های نوار مراحل (اختیاری)؛ صحنه با step شماره مرحله‌اش را می‌گوید
 *   intro / outro   کارت آغاز و پایان: kicker، heading، text، art، caption
 *   scenes   فهرست صحنه‌ها: chapter (نام دکمه فصل)، caption (زیرنویس)، و یکی از:
 *     widgets  اجزای داخل پنجره، به ترتیب اتفاق افتادن:
 *       heading {text, sub?}           text {text}
 *       choice {items: [[عنوان, توضیح]], pick?, marker?: radio|check|none}
 *       fields {items: [{label, value, typed?, full?}]}
 *       search {label?, value}         textarea {label, value}
 *       toggle {label}  check {label}  button {label, variant?: secondary}
 *       table {head, rows}             stats {items: [[برچسب, عدد]]}
 *       note {text, tone?: caution}    badge {text, tone?}
 *       track {items: [مرحله…]}        upload {label, file, result}
 *       sheet {title, code, stamp}     برگه‌ای که از پنجره بیرون می‌آید
 *     phone    صفحه گوشی: {title, code?, scan?, ok, lines: [[برچسب, مقدار]]}، با side: art اختیاری
 *
 * مقدار لاتین در متن داخل [[…]] نوشته می‌شود تا چپ‌به‌راست بماند (مثل راهنماها).
 */
final class MotionTutorials
{
    /** @var array<string, string> کلید راهنما ← مسیر فایل تعریف */
    private array $files = [];

    /** @var array<string, MotionScript> */
    private array $built = [];

    public function register(string $topic, string $file): void
    {
        $this->files[$topic] = $file;
    }

    public function has(string $topic): bool
    {
        return isset($this->files[$topic]);
    }

    public function for(string $topic): ?MotionScript
    {
        $file = $this->files[$topic] ?? null;

        if ($file === null) {
            return null;
        }

        if (! isset($this->built[$file])) {
            $definition = require $file;

            if (! is_array($definition)) {
                throw new InvalidArgumentException("تعریف آموزش {$file} آرایه برنمی‌گرداند.");
            }

            /** @var array<string, mixed> $definition */
            $this->built[$file] = MotionScript::build(basename($file, '.php'), $definition);
        }

        return $this->built[$file];
    }

    /** @return list<string> */
    public function topics(): array
    {
        return array_keys($this->files);
    }
}
