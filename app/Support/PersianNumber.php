<?php

declare(strict_types=1);

namespace App\Support;

/**
 * قالب‌بندی عدد برای خواننده فارسی.
 *
 * جداکننده هزارگان، علامت ممیز فارسی (U+066C) است نه ویرگول لاتین؛ در متن
 * راست‌چین ویرگول لاتین با ویرگول جمله اشتباه گرفته می‌شود.
 */
final readonly class PersianNumber
{
    /** جداکننده هزارگان فارسی (ARABIC THOUSANDS SEPARATOR). */
    private const THOUSANDS = '٬';

    /** علامت اعشار فارسی (ARABIC DECIMAL SEPARATOR). */
    private const DECIMAL = '٫';

    /** «۱۲۳۴۵۶۷» → «۱٬۲۳۴٬۵۶۷» */
    public static function format(int $value): string
    {
        return PersianDigits::from(number_format($value, 0, '.', self::THOUSANDS));
    }

    /**
     * عدد اعشاری برای متن فارسی: «۲۳٫۵»
     *
     * مقدار اندازه‌گیری علمی از این مسیر نمی‌گذرد؛ آن با ارقام لاتین و نشانه
     * data-numeric نمایش داده می‌شود تا با واحدش وارونه نشود.
     */
    public static function decimal(float $value, int $precision = 1): string
    {
        return PersianDigits::from(number_format($value, $precision, self::DECIMAL, self::THOUSANDS));
    }

    /** «۱۲٪» */
    public static function percent(float $value, int $precision = 0): string
    {
        return self::decimal($value, $precision).'٪';
    }

    /** «۱۲۳۴» → «۱۲۳۴» بدون جداکننده؛ برای شماره و شناسه، نه مقدار. */
    public static function digitsOnly(string|int $value): string
    {
        return PersianDigits::from((string) $value);
    }
}
