<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Domain\Enums;

/**
 * نوع تغییر یک نسخه حقوقی (DEC-24).
 *
 * فقط تغییر اساسی کاربر را تا پذیرش دوباره متوقف می‌کند. غلط‌گیری یا اصلاح
 * نشانی تماس نباید همه کاربران را از میزکارشان بیرون کند.
 */
enum LegalChange: string
{
    case Material = 'material';
    case Minor = 'minor';

    public function label(): string
    {
        return match ($this) {
            self::Material => 'تغییر اساسی — نیازمند پذیرش دوباره',
            self::Minor => 'اصلاح جزئی',
        };
    }
}
