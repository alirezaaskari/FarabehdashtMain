<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Domain\Enums;

/**
 * جایگاه آگهی در چرخه عمرش، از روی ستون‌های زمانی حساب می‌شود و ذخیره
 * نمی‌شود؛ وضعیت بازبینی جداگانه در {@see ReviewStatus} است.
 */
enum PostingState: string
{
    // هنوز یک بار هم منتشر نشده: پیش‌نویس، در انتظار تأیید یا برگشتی.
    case Unpublished = 'unpublished';
    // تأیید شده و منتظر پرداخت است.
    case AwaitingPayment = 'awaiting_payment';
    case Live = 'live';
    case Expired = 'expired';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Unpublished => 'منتشرنشده',
            self::AwaitingPayment => 'آماده پرداخت',
            self::Live => 'منتشرشده',
            self::Expired => 'منقضی',
            self::Closed => 'بسته‌شده',
        };
    }

    /** رنگ نشان در فهرست کارفرما؛ همان چهار رنگ کامپوننت badge. */
    public function tone(): string
    {
        return match ($this) {
            self::Live => 'primary',
            self::AwaitingPayment => 'caution',
            self::Expired, self::Closed => 'danger',
            self::Unpublished => 'neutral',
        };
    }
}
