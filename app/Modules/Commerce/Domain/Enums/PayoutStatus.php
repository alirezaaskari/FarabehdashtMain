<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Domain\Enums;

enum PayoutStatus: string
{
    case Requested = 'requested';
    case Paid = 'paid';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'در انتظار واریز',
            self::Paid => 'واریز شد',
            self::Rejected => 'رد شد',
            self::Cancelled => 'لغو شد',
        };
    }

    /** نام لحن `<x-badge>`. */
    public function tone(): string
    {
        return match ($this) {
            self::Requested => 'caution',
            self::Paid => 'primary',
            self::Rejected => 'danger',
            self::Cancelled => 'neutral',
        };
    }
}
