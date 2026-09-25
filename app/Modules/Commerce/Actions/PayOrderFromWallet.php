<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Actions;

use App\Modules\Commerce\Domain\Order;
use App\Support\Payments\PaymentSource;
use App\Support\Payments\WalletCheckout;

/**
 * پرداخت سفارش از کیف پول خریدار (DEC-37)، بدون رفتن به درگاه.
 *
 * اثر مالی همان `CompleteOrderPayment` است؛ کلید idempotency از uuid سفارش
 * می‌آید، پس دو کلیک پشت‌سرهم کیف پول را دو بار کم نمی‌کند.
 */
final readonly class PayOrderFromWallet
{
    public function __construct(
        private WalletCheckout $wallet,
        private CompleteOrderPayment $complete,
    ) {}

    public function handle(Order $order): Order
    {
        $this->wallet->assertCanPay($order->buyer_user_id, $order->total());

        return $this->complete->handle($order, null, PaymentSource::Wallet);
    }
}
