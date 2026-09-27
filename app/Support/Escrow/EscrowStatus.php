<?php

declare(strict_types=1);

namespace App\Support\Escrow;

/**
 * سرنوشت پولی که تا پایان یک خدمت نزد سایت مانده (بخش ۱۹-۱).
 *
 * فقط `Held` باز است؛ سه حالت دیگر پایانی‌اند و هر کدام دقیقاً یک تراکنش
 * دفتر کل پشتشان دارد.
 */
enum EscrowStatus: string
{
    case Held = 'held';
    case Released = 'released';
    case Refunded = 'refunded';
    case Split = 'split';

    public function label(): string
    {
        return match ($this) {
            self::Held => 'نزد سایت',
            self::Released => 'پرداخت‌شده به ارائه‌دهنده',
            self::Refunded => 'بازگشت به خریدار',
            self::Split => 'تقسیم با رأی مدیر',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::Held;
    }
}
