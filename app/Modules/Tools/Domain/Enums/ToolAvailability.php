<?php

declare(strict_types=1);

namespace App\Modules\Tools\Domain\Enums;

/**
 * وضعیت در دسترس بودن یک ابزار برای کاربر.
 *
 * «بازبینی عقب‌افتاده» عمداً حالت سومی است و نه یک پرچم کنار «فعال»: ابزاری
 * که تاریخ بازبینی‌اش گذشته همچنان کار می‌کند، ولی کاربر باید بداند و مدیر
 * باید در صف کارش ببیند.
 */
enum ToolAvailability: string
{
    case Available = 'available';
    case ReviewOverdue = 'review_overdue';
    case Disabled = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'در دسترس',
            self::ReviewOverdue => 'بازبینی عقب‌افتاده',
            self::Disabled => 'غیرفعال',
        };
    }

    public function usable(): bool
    {
        return $this !== self::Disabled;
    }
}
