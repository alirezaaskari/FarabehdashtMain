<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Actions;

use App\Modules\Bundles\Domain\BundlePurchase;
use App\Support\Payments\PaymentSource;
use App\Support\Payments\WalletCheckout;

final readonly class PayBundleFromWallet
{
    public function __construct(
        private WalletCheckout $wallet,
        private CompleteBundlePurchase $complete,
    ) {}

    public function handle(BundlePurchase $purchase): BundlePurchase
    {
        $this->wallet->assertCanPay($purchase->user_id, $purchase->price());

        return $this->complete->handle($purchase, null, PaymentSource::Wallet);
    }
}
