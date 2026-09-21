<?php

declare(strict_types=1);

namespace App\Modules\Courses\Domain\Enums;

/**
 * وضعیت یک ثبت‌نام — همان الگوی `OrderStatus` ماژول تجارت.
 *
 * بازگشت وجه دوره در این بخش ساخته نشده (README ماژول)، پس وضعیتی برایش
 * نیست؛ افزودنش بعداً فقط یک Case تازه است.
 */
enum EnrollmentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار پرداخت',
            self::Paid => 'پرداخت‌شده',
            self::Failed => 'ناموفق',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'caution',
            self::Paid => 'primary',
            self::Failed => 'danger',
        };
    }

    /** آیا این ثبت‌نام دسترسی به محیط یادگیری می‌دهد. */
    public function grantsAccess(): bool
    {
        return $this === self::Paid;
    }
}
