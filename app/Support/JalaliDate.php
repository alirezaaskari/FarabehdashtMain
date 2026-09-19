<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeInterface;
use DateTimeZone;
use IntlDateFormatter;
use RuntimeException;

/**
 * تاریخ شمسی، بدون بسته جانبی.
 *
 * تصمیم (ADR-0001): افزونه intl خودش تقویم جلالی را می‌شناسد، پس بسته‌ای مثل
 * morilog/jalali وابستگی اضافه و تکراری است. تقویم `persian` در ICU همان
 * تقویم هجری شمسی رسمی ایران است.
 *
 * منطقه زمانی همیشه Asia/Tehran است مگر خلافش خواسته شود؛ کاربر این سایت در
 * ایران است و تاریخ UTC برایش معنی ندارد.
 */
final readonly class JalaliDate
{
    private const LOCALE = 'fa_IR@calendar=persian';

    private const TIMEZONE = 'Asia/Tehran';

    /** «۲۸ شهریور ۱۴۰۵» */
    public static function long(DateTimeInterface $date): string
    {
        return self::format($date, 'd MMMM y');
    }

    /** «۲۸ شهریور ۱۴۰۵، ساعت ۱۴:۳۰» */
    public static function longWithTime(DateTimeInterface $date): string
    {
        return self::format($date, "d MMMM y، 'ساعت' HH:mm");
    }

    /** «۱۴۰۵/۰۶/۲۸» */
    public static function short(DateTimeInterface $date): string
    {
        return self::format($date, 'y/MM/dd');
    }

    /** «شنبه ۲۸ شهریور» — برای تقویم و برنامه جلسه */
    public static function dayAndMonth(DateTimeInterface $date): string
    {
        return self::format($date, 'EEEE d MMMM');
    }

    /**
     * سال، ماه و روز شمسی به‌صورت عدد — برای گروه‌بندی گزارش.
     *
     * @return array{year: int, month: int, day: int}
     */
    public static function parts(DateTimeInterface $date): array
    {
        return [
            'year' => (int) PersianDigits::toLatin(self::format($date, 'y')),
            'month' => (int) PersianDigits::toLatin(self::format($date, 'M')),
            'day' => (int) PersianDigits::toLatin(self::format($date, 'd')),
        ];
    }

    /**
     * قالب دلخواه ICU.
     *
     * @throws RuntimeException اگر افزونه intl نصب نباشد یا قالب نامعتبر باشد
     */
    public static function format(DateTimeInterface $date, string $pattern): string
    {
        if (! extension_loaded('intl')) {
            throw new RuntimeException('افزونه intl برای تاریخ شمسی لازم است.');
        }

        $formatter = new IntlDateFormatter(
            self::LOCALE,
            IntlDateFormatter::FULL,
            IntlDateFormatter::NONE,
            new DateTimeZone(self::TIMEZONE),
            IntlDateFormatter::TRADITIONAL,
            $pattern,
        );

        $formatted = $formatter->format($date);

        if ($formatted === false) {
            throw new RuntimeException("قالب تاریخ نامعتبر است: {$pattern}");
        }

        return $formatted;
    }
}
