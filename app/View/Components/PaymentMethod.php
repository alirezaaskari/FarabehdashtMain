<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Support\Money;
use App\Support\Payments\PaymentSource;
use App\Support\Payments\WalletCheckout;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * انتخاب روش پرداخت در فرم خرید: درگاه یا کیف پول (DEC-37).
 *
 * فقط وقتی دیده می‌شود که کاربر وارد شده و ماژول دفتر کل فعال است؛ در غیر
 * این صورت چیزی نمی‌کشد و فرم مثل قبل به درگاه می‌رود. گزینه کیف پول با
 * موجودی ناکافی غیرفعال است و موجودی کنارش نوشته می‌شود.
 */
final class PaymentMethod extends Component
{
    public readonly ?Money $balance;

    public readonly bool $walletCoversTotal;

    /** صفحه Pro دو فرم خرید دارد؛ شناسه هر توضیح موجودی باید یکتا باشد. */
    public readonly string $balanceId;

    private static int $instances = 0;

    public function __construct(
        public readonly Money $total,
        WalletCheckout $wallet,
        Guard $auth,
        /** روی پس‌زمینه سبز ستون برجسته صفحه Pro. */
        public readonly bool $inverse = false,
    ) {
        $userId = $auth->id();

        $this->balance = $userId === null ? null : $wallet->balanceOf((int) $userId);
        $this->walletCoversTotal = $userId !== null && $wallet->canPay((int) $userId, $total);
        $this->balanceId = 'wallet-balance-'.++self::$instances;
    }

    public function shouldRender(): bool
    {
        return $this->balance !== null && ! $this->total->isZero();
    }

    public function render(): View
    {
        return view('components.payment-method', [
            'gateway' => PaymentSource::Gateway,
            'wallet' => PaymentSource::Wallet,
        ]);
    }
}
