<?php

declare(strict_types=1);

namespace App\Modules\Expert\Domain\Enums;

/**
 * وضعیت پرسش یا پاسخ در صف تأیید مدیر.
 *
 * پرسش و پاسخ هر دو پیش از دیده‌شدن از همین صف می‌گذرند (قاعده محتوای
 * مشاور)، پس یک Enum برای هر دو کافی است.
 */
enum ReviewStatus: string
{
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار تأیید',
            self::Published => 'منتشرشده',
            self::Rejected => 'ردشده',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'caution',
            self::Published => 'primary',
            self::Rejected => 'danger',
        };
    }
}
