<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Enums;

/**
 * سرنوشت یک پیام گفت‌وگو (DEC-80). پیام نگه‌داشته فقط برای فرستنده و مدیر
 * دیده می‌شود تا مدیر تصمیم بگیرد.
 */
enum MessageStatus: string
{
    case Delivered = 'delivered';
    case Held = 'held';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Delivered => 'رسید',
            self::Held => 'در انتظار بررسی مدیر',
            self::Rejected => 'رد شد؛ به طرف مقابل نرسید',
        };
    }
}
