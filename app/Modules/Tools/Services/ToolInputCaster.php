<?php

declare(strict_types=1);

namespace App\Modules\Tools\Services;

use App\Support\PersianDigits;
use Farabehdasht\CalcEngine\Input\InputDefinition;

/**
 * تبدیل ورودی فرم به چیزی که موتور محاسبات می‌فهمد.
 *
 * دو قاعده:
 *
 * ۱. چیزی که عدد نیست، عدد نمی‌شود. `(float) 'سه'` در PHP صفر است و همین
 *    یک تبدیل خاموش می‌تواند یک گزارش مواجهه را بی‌سروصدا غلط کند. اینجا
 *    رشته غیرعددی دست‌نخورده رد می‌شود تا اعتبارسنجی موتور ردش کند.
 *
 * ۲. فیلد خالی حذف می‌شود، نه اینکه صفر شود. موتور آن‌وقت می‌گوید «الزامی
 *    است»، که حقیقت است — «صفر» حقیقت نیست.
 *
 * ارقام فارسی و عربی و جداکننده‌های فارسی پذیرفته می‌شوند: کاربر میدانی با
 * کیبورد فارسی تایپ می‌کند و رد کردن «۲۵» آزار بی‌دلیل است.
 */
final class ToolInputCaster
{
    /**
     * @param  array<string, InputDefinition>  $definitions
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    public function cast(array $definitions, array $request): array
    {
        $cast = [];

        foreach ($definitions as $key => $definition) {
            if (! array_key_exists($key, $request)) {
                continue;
            }

            $value = $definition->list
                ? $this->castList($request[$key])
                : $this->castNumber($request[$key]);

            if ($value === null || $value === []) {
                continue;
            }

            $cast[$key] = $value;
        }

        return $cast;
    }

    /**
     * @return list<mixed>
     */
    private function castList(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [$this->castNumber($raw)];
        }

        $values = [];

        foreach ($raw as $item) {
            $value = $this->castNumber($item);

            // ردیف خالی فرم حذف می‌شود؛ کاربر همیشه ردیف‌های آماده را پر
            // نمی‌کند و خالی‌بودنشان خطا نیست.
            if ($value !== null) {
                $values[] = $value;
            }
        }

        return $values;
    }

    private function castNumber(mixed $raw): int|float|string|null
    {
        if (is_int($raw) || is_float($raw)) {
            return $raw;
        }

        if (! is_string($raw)) {
            return null;
        }

        $normalised = str_replace(
            ['٫', '٬', ',', ' ', "\u{200c}"],
            ['.', '', '', '', ''],
            PersianDigits::toLatin(trim($raw)),
        );

        if ($normalised === '') {
            return null;
        }

        return is_numeric($normalised) ? (float) $normalised : $raw;
    }
}
