<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Actions;

use App\Modules\Webinars\Domain\WebinarRegistration;
use App\Support\Payments\PaymentSource;
use App\Support\Payments\WalletCheckout;

final readonly class PayRegistrationFromWallet
{
    public function __construct(
        private WalletCheckout $wallet,
        private CompleteRegistration $complete,
    ) {}

    public function handle(WebinarRegistration $purchase): WebinarRegistration
    {
        $this->wallet->assertCanPay($purchase->user_id, $purchase->price());

        return $this->complete->handle($purchase, null, PaymentSource::Wallet);
    }
}
