<?php

declare(strict_types=1);

namespace App\Support\Payments;

use App\Support\Money;
use Illuminate\Support\Carbon;

/**
 * یک خرید در صفحه «بازگشت وجه خریدهای دیگر»، پیش از اجرا.
 *
 * `refundable` همان مبلغی است که اگر همین حالا برگردد به کیف پول می‌رود؛
 * برای اشتراک و تیم فقط سهم روزهای مانده. `blocked` دلیل برگشت‌ناپذیری است.
 */
final readonly class RefundablePurchase
{
    public function __construct(
        public string $uuid,
        public string $title,
        public Money $paid,
        public Money $refundable,
        public ?Carbon $paidAt,
        public string $effect,
        public bool $refunded = false,
        public ?string $blocked = null,
    ) {}

    public function canRefund(): bool
    {
        return ! $this->refunded && $this->blocked === null && ! $this->refundable->isZero();
    }
}
