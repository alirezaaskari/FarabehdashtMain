<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Enums;

/** وضعیت قرارداد بازار پروژه (بخش ۲۱-۴). */
enum ContractStatus: string
{
    case AwaitingPayment = 'awaiting_payment';
    case Active = 'active';
    case Completed = 'completed';
    case Lapsed = 'lapsed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingPayment => 'در انتظار پرداخت مرحله اول',
            self::Active => 'در حال انجام',
            self::Completed => 'تمام‌شده',
            self::Lapsed => 'بی‌اثر شد',
            self::Cancelled => 'لغو شد',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::AwaitingPayment => 'caution',
            self::Active, self::Completed => 'primary',
            self::Lapsed, self::Cancelled => 'neutral',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::AwaitingPayment || $this === self::Active;
    }
}
