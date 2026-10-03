<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Enums;

/**
 * چرخه یک پروژه بازار.
 *
 * پروژه فقط پیش از انتشار ویرایش می‌شود (در انتظار یا برگشته)؛ پروژه باز
 * فقط بسته می‌شود تا پیشنهادهای رسیده روی متن دیگری نمانند.
 */
enum ProjectStatus: string
{
    case Pending = 'pending';
    case Rejected = 'rejected';
    case Open = 'open';
    case Awarded = 'awarded';
    case Completed = 'completed';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار تأیید',
            self::Rejected => 'برگشت برای اصلاح',
            self::Open => 'باز برای پیشنهاد',
            self::Awarded => 'در حال انجام',
            self::Completed => 'تحویل‌شده',
            self::Closed => 'بسته‌شده',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Open, self::Completed => 'primary',
            self::Pending, self::Rejected, self::Awarded => 'caution',
            self::Closed => 'danger',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Pending || $this === self::Rejected;
    }

    /** پروژه‌ای که یک بار منتشر شده و صفحه عمومی دارد. */
    public function isPublished(): bool
    {
        return in_array($this, [self::Open, self::Awarded, self::Completed, self::Closed], true);
    }
}
