<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Actions;

use App\Modules\ExamPrep\Domain\PackPurchase;
use App\Support\Payments\PaymentSource;
use App\Support\Payments\WalletCheckout;

final readonly class PayPackFromWallet
{
    public function __construct(
        private WalletCheckout $wallet,
        private CompletePackPurchase $complete,
    ) {}

    public function handle(PackPurchase $purchase): PackPurchase
    {
        $this->wallet->assertCanPay($purchase->user_id, $purchase->price());

        return $this->complete->handle($purchase, null, PaymentSource::Wallet);
    }
}
