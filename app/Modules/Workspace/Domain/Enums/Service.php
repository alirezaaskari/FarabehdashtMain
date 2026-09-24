<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Domain\Enums;

/**
 * شش سرویسی که صفحه وضعیت گزارش می‌کند.
 *
 * فهرست ثابت است و از ماژول‌ها پرسیده نمی‌شود: صفحه وضعیت درست همان وقتی
 * خوانده می‌شود که بخشی از سایت خراب است، پس نباید به سالم‌بودن ماژول‌ها
 * تکیه کند.
 */
enum Service: string
{
    case Site = 'site';
    case Otp = 'otp';
    case Payments = 'payments';
    case Downloads = 'downloads';
    case Reports = 'reports';
    case Search = 'search';

    public function label(): string
    {
        return match ($this) {
            self::Site => 'سایت و میزکار',
            self::Otp => 'ورود با کد یک‌بارمصرف',
            self::Payments => 'پرداخت',
            self::Downloads => 'دانلود فایل‌ها',
            self::Reports => 'گزارش‌ساز',
            self::Search => 'جست‌وجو',
        };
    }
}
