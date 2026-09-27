<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Domain\Enums;

enum ServiceStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'پیش‌نویس',
            self::Pending => 'در انتظار تأیید',
            self::Published => 'منتشرشده',
            self::Rejected => 'برگشت برای اصلاح',
            self::Retired => 'از فروش خارج شده',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Rejected, self::Published], true);
    }
}
