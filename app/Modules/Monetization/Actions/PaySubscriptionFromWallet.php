<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Actions;

use App\Models\User;
use App\Modules\Monetization\Domain\Plan;
use App\Modules\Monetization\Domain\SubscriptionPeriod;
use App\Support\Payments\PaymentSource;
use App\Support\Payments\WalletCheckout;

/**
 * خرید یا تمدید اشتراک از کیف پول (DEC-37)، بدون رفتن به درگاه.
 *
 * موجودی پیش از ساختن دوره بررسی می‌شود تا کیف پول ناکافی ردیف بی‌صاحب
 * نگذارد.
 */
final readonly class PaySubscriptionFromWallet
{
    public function __construct(
        private WalletCheckout $wallet,
        private OpenSubscriptionPeriod $openPeriod,
        private CompleteSubscriptionPayment $complete,
    ) {}

    public function handle(User $user, Plan $plan): SubscriptionPeriod
    {
        $this->wallet->assertCanPay((int) $user->getKey(), $plan->price());

        $period = $this->openPeriod->handle($user, $plan);
        $this->complete->handle($period, null, PaymentSource::Wallet);

        return $period->refresh();
    }
}
