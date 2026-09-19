<?php

declare(strict_types=1);

namespace App\Support;

/**
 * تبدیل ارقام بین فارسی، عربی و لاتین.
 *
 * قاعده طراحی: عدد داخل جمله فارسی با ارقام فارسی نوشته می‌شود («۲ دقیقه»)،
 * ولی مقدار اندازه‌گیری، کد، شماره و شناسه با ارقام لاتین و در جهت چپ‌به‌راست
 * می‌ماند (نشانه [data-numeric]) تا با واحد و علامت اعشار قاطی نشود.
 */
final readonly class PersianDigits
{
    private const LATIN = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    private const PERSIAN = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    private const ARABIC = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    /** برای نمایش: ارقام لاتین را فارسی می‌کند. */
    public static function from(string|int|float $value): string
    {
        return str_replace(self::LATIN, self::PERSIAN, (string) $value);
    }

    /** برای پردازش: هر رقم فارسی یا عربی را لاتین می‌کند. */
    public static function toLatin(string $value): string
    {
        return str_replace(
            [...self::PERSIAN, ...self::ARABIC],
            [...self::LATIN, ...self::LATIN],
            $value,
        );
    }
}
