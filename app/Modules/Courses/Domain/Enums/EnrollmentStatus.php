<?php

declare(strict_types=1);

namespace App\Modules\Courses\Domain\Enums;

/**
 * وضعیت یک ثبت‌نام — همان الگوی `OrderStatus` ماژول تجارت.
 *
 * «وجه برگشت» (بخش ۱۸-۱۱) دسترسی را می‌بندد؛ بازگشت همیشه کامل است.
 */
enum EnrollmentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار پرداخت',
            self::Paid => 'پرداخت‌شده',
            self::Failed => 'ناموفق',
            self::Refunded => 'وجه برگشت داده شد',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'caution',
            self::Paid => 'primary',
            self::Failed => 'danger',
            self::Refunded => 'neutral',
        };
    }

    /** آیا این ثبت‌نام دسترسی به محیط یادگیری می‌دهد. */
    public function grantsAccess(): bool
    {
        return $this === self::Paid;
    }
}
