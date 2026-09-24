<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Domain\Enums;

/**
 * وضعیت یک دوره پرداخت اشتراک.
 *
 * دوره همان نقش سفارش را برای اشتراک بازی می‌کند: پیش از تأیید درگاه هیچ
 * اثر مالی ندارد و هیچ روزی به اشتراک اضافه نمی‌کند.
 */
enum PeriodStatus: string
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
