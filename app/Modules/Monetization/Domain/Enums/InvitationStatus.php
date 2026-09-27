<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Domain\Enums;

/** وضعیت دعوت به تیم. دعوت در انتظار یک صندلی را نگه می‌دارد. */
enum InvitationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار پاسخ',
            self::Accepted => 'پذیرفته',
            self::Declined => 'ردشده',
            self::Cancelled => 'لغوشده',
        };
    }
}
