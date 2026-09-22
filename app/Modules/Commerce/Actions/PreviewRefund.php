<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Actions;

use App\Modules\Commerce\Domain\Enums\OrderStatus;
use App\Modules\Commerce\Domain\OrderItem;
use App\Modules\Commerce\Domain\RefundEffect;
use App\Support\Money;
use InvalidArgumentException;

/**
 * محاسبه اثر مالی یک بازگشت وجه، بدون نوشتن.
 *
 * همان محاسبه‌ای که {@see IssueRefund} واقعاً اجرا می‌کند — یک منطق، دو
 * مصرف (پیش‌نمایش پنل مدیریت و اجرای واقعی)، تا پیش‌نمایش هرگز از واقعیت
 * عقب نماند.
 */
final readonly class PreviewRefund
{
    public function handle(OrderItem $item, Money $amount): RefundEffect
    {
        $this->guard($item, $amount);

        $commissionPortion = $amount->percentage($item->commission_rate_bp / 100);

        return new RefundEffect(
            walletCredit: $amount,
            vendorPayableDebit: $amount->minus($commissionPortion),
            platformRevenueDebit: $commissionPortion,
        );
    }

    public function guard(OrderItem $item, Money $amount): void
    {
        if ($amount->isZero()) {
            throw new InvalidArgumentException('مبلغ بازگشتی باید بزرگ‌تر از صفر باشد.');
        }

        if ($amount->isGreaterThan($item->remainingRefundable())) {
            throw new InvalidArgumentException('مبلغ بازگشتی از باقیمانده قابل‌برگشت این ردیف بیشتر است.');
        }

        if (! in_array($item->order->status, [OrderStatus::Paid, OrderStatus::PartiallyRefunded], true)) {
            throw new InvalidArgumentException('فقط سفارش پرداخت‌شده بازگشت وجه می‌گیرد.');
        }
    }
}
