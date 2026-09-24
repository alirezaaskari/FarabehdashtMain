<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\Enums;

/**
 * چهار مرحله گزارش‌ساز. مرحله اول (انتخاب منبع) پیش‌نویس را می‌سازد؛ سه
 * مرحله بعدی روی پیش‌نویس موجود کار می‌کنند.
 */
enum ReportStep: int
{
    case Source = 1;
    case Details = 2;
    case Findings = 3;
    case Review = 4;

    public function label(): string
    {
        return match ($this) {
            self::Source => 'منبع',
            self::Details => 'مشخصات',
            self::Findings => 'یافته‌ها',
            self::Review => 'بازبینی و صدور',
        };
    }
}
