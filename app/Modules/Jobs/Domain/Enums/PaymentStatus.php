<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Domain\Enums;

/**
 * وضعیت پرداخت یک دوره انتشار آگهی. پیش از تأیید درگاه هیچ روزی به آگهی
 * اضافه نمی‌شود.
 */
enum PaymentStatus: string
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
}
