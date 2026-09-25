<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Domain\Enums;

/**
 * بررسی مدیر روی یک نسخه فایل.
 *
 * نسخه‌ای که فروشنده به محصول منتشرشده می‌افزاید تا تأیید به خریدار
 * نمی‌رسد؛ خریدار همان نسخه تأییدشده قبلی را دانلود می‌کند. نسخه ردشده
 * حذف نمی‌شود (نسخه‌ها فقط افزودنی‌اند)، فقط هرگز به خریدار نمی‌رسد.
 */
enum VersionReviewStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار تأیید مدیر',
            self::Approved => 'تأییدشده',
            self::Rejected => 'رد شده',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'caution',
            self::Approved => 'primary',
            self::Rejected => 'danger',
        };
    }
}
