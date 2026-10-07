<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Domain\Enums;

/** سرنوشت یک گزارش اشتباه. */
enum ErrorReportStatus: string
{
    case Open = 'open';
    case Fixed = 'fixed';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'در انتظار بررسی',
            self::Fixed => 'اصلاح شد',
            self::Dismissed => 'رد شد',
        };
    }
}
