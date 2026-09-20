<?php

declare(strict_types=1);

namespace App\Modules\Tools\Services;

/**
 * برگرداندن ورودی‌های ذخیره‌شده به شکل خام، برای بازتولید محاسبه.
 *
 * محاسبه با واحد ذخیره می‌شود (`{"value": 25.0, "unit": "celsius"}`) تا
 * گزارش چاپی بدون دانستن فرمول هم خوانا باشد. برای اجرای دوباره باید همان
 * عددها بیرون کشیده شوند.
 */
final class StoredInputs
{
    /**
     * @param  array<string, mixed>  $stored
     * @return array<string, float|list<float>>
     */
    public static function toRaw(array $stored): array
    {
        $raw = [];

        foreach ($stored as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            $raw[$key] = array_key_exists('value', $value)
                ? (float) $value['value']
                : array_values(array_map(
                    static fn (mixed $item): float => is_array($item) ? (float) $item['value'] : (float) $item,
                    $value,
                ));
        }

        return $raw;
    }
}
