<?php

declare(strict_types=1);

namespace App\Modules\Tools\Services;

/**
 * قالب‌بندی مقدار اندازه‌گیری برای نمایش.
 *
 * قاعده طراحی پروژه: مقدار اندازه‌گیری با ارقام **لاتین** و چپ‌به‌راست می‌ماند
 * (`data-numeric`)، برخلاف عدد داخل جمله فارسی که با `@fa` چاپ می‌شود. این دو
 * هرگز قاطی نمی‌شوند.
 *
 * گرد کردن فقط برای نمایش است. مقدار کامل در محاسبه ذخیره‌شده می‌ماند تا
 * بازتولید دقیق ممکن بماند.
 */
final class MeasurementNumber
{
    private const int DECIMALS = 4;

    public static function format(float $value): string
    {
        if (! is_finite($value)) {
            return '—';
        }

        $formatted = number_format($value, self::DECIMALS, '.', '');

        // صفرهای انتهایی حذف می‌شوند: «28» خواناتر از «28.0000» است، ولی
        // «0.8421» باید کامل بماند.
        return str_contains($formatted, '.')
            ? rtrim(rtrim($formatted, '0'), '.')
            : $formatted;
    }
}
