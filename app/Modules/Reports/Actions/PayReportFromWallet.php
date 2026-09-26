<?php

declare(strict_types=1);

namespace App\Modules\Reports\Actions;

use App\Modules\Reports\Domain\ReportPurchase;
use App\Support\Payments\PaymentSource;
use App\Support\Payments\WalletCheckout;

/**
 * پرداخت صدور گزارش از کیف پول (DEC-37)، بدون رفتن به درگاه.
 */
final readonly class PayReportFromWallet
{
    public function __construct(
        private WalletCheckout $wallet,
        private CompleteReportPurchase $complete,
    ) {}

    public function handle(ReportPurchase $purchase): ReportPurchase
    {
        $this->wallet->assertCanPay($purchase->user_id, $purchase->price());

        return $this->complete->handle($purchase, null, PaymentSource::Wallet);
    }
}
