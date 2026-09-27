<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Domain\Enums;

/**
 * چرخه یک درخواست خدمت.
 *
 * ```
 * AwaitingPayment → (پرداخت، پول در امانت) AwaitingConsultant
 *   → Accepted → Delivered → Completed (آزادسازی: تأیید خریدار یا ۷ روز، DEC-54)
 *                          → Disputed → Resolved (رأی مدیر)
 *   → Declined | Expired (۴۸ ساعت، DEC-53) — بازگشت کامل به کیف پول (DEC-56)
 * AwaitingPayment → Failed (پرداخت انجام نشد)
 * ```
 */
enum OrderStatus: string
{
    case AwaitingPayment = 'awaiting_payment';
    case Failed = 'failed';
    case AwaitingConsultant = 'awaiting_consultant';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Expired = 'expired';
    case Delivered = 'delivered';
    case Completed = 'completed';
    case Disputed = 'disputed';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingPayment => 'در انتظار پرداخت',
            self::Failed => 'پرداخت انجام نشد',
            self::AwaitingConsultant => 'در انتظار پاسخ مشاور',
            self::Accepted => 'پذیرفته شد',
            self::Declined => 'رد شد، پول برگشت',
            self::Expired => 'بی‌پاسخ ماند، پول برگشت',
            self::Delivered => 'انجام شد، منتظر تأیید شما',
            self::Completed => 'تمام شد',
            self::Disputed => 'اعتراض در بررسی مدیر',
            self::Resolved => 'اعتراض بررسی شد',
        };
    }

    /** پولی در امانت است و کار هنوز باز است. */
    public function isOpen(): bool
    {
        return in_array($this, [self::AwaitingConsultant, self::Accepted, self::Delivered, self::Disputed], true);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Completed, self::Accepted => 'primary',
            self::Disputed, self::AwaitingConsultant, self::Delivered => 'caution',
            self::Failed => 'danger',
            default => 'neutral',
        };
    }
}
