<?php

declare(strict_types=1);

namespace App\Support\Payments;

use App\Contracts\FinancialGuard;
use App\Contracts\WalletStatementReader;
use App\Support\Money;
use Illuminate\Contracts\Container\Container;

/**
 * پیش‌شرط‌های مشترک پرداخت از کیف پول (DEC-37) برای هر ماژولی که می‌فروشد.
 *
 * کیف پول فقط وقتی گزینه است که ماژول دفتر کل فعال باشد؛ خاموش‌شدنش فقط
 * این گزینه را پنهان می‌کند و پرداخت با درگاه دست‌نخورده می‌ماند.
 *
 * بررسی موجودی این‌جا برای پیام روشن به کاربر است. ضامن واقعی «کیف پول منفی
 * نمی‌شود» خود دفتر کل است که ردیف کیف پول را هنگام بدهکارکردن قفل می‌کند.
 */
final readonly class WalletCheckout
{
    public function __construct(
        private Container $container,
        private FinancialGuard $guard,
    ) {}

    public function available(): bool
    {
        return $this->container->bound(WalletStatementReader::class);
    }

    public function balanceOf(int $userId): ?Money
    {
        return $this->available()
            ? $this->container->make(WalletStatementReader::class)->balanceOf($userId)
            : null;
    }

    public function canPay(int $userId, Money $amount): bool
    {
        $balance = $this->balanceOf($userId);

        return $balance !== null && ! $amount->isZero() && ! $balance->isLessThan($amount);
    }

    /**
     * @throws FinancialActionBlocked در حالت «مشاهده به‌عنوان کاربر»
     * @throws InsufficientWalletBalance
     */
    public function assertCanPay(int $userId, Money $amount): void
    {
        $this->guard->assertAllowed();

        if (! $this->canPay($userId, $amount)) {
            throw InsufficientWalletBalance::make();
        }
    }
}
