<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Domain\Enums;

/**
 * وضعیت یک سفارش.
 *
 * سفارش پیش از موفقیت درگاه هیچ اثر مالی ندارد — هیچ ردیف دفتر کل برایش
 * نوشته نمی‌شود تا `Pending` به `Paid` برسد. «ناموفق» یعنی درگاه خودش رد
 * کرد یا مبلغ تأییدشده با مبلغ سفارش نخواند؛ این هم اثر مالی ندارد.
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار پرداخت',
            self::Paid => 'پرداخت‌شده',
            self::Failed => 'ناموفق',
            self::PartiallyRefunded => 'بازگشت جزئی وجه',
            self::Refunded => 'بازگشت کامل وجه',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'caution',
            self::Paid => 'primary',
            self::Failed => 'danger',
            self::PartiallyRefunded, self::Refunded => 'neutral',
        };
    }

    /** آیا این سفارش دسترسی دانلود می‌دهد — بازگشت کامل دسترسی را باطل می‌کند. */
    public function grantsAccess(): bool
    {
        return $this === self::Paid || $this === self::PartiallyRefunded;
    }
}
