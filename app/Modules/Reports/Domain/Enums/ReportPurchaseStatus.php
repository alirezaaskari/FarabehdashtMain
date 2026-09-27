<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\Enums;

enum ReportPurchaseStatus: string
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
            self::Refunded => 'بازگشت‌خورده',
        };
    }
}
