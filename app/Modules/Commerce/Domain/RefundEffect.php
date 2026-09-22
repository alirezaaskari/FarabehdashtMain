<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Domain;

use App\Support\Money;

/**
 * اثر مالی یک بازگشت وجه — پیش از اجرا و پس از اجرا یکسان محاسبه می‌شود
 * (`docs/architecture/admin-panel.md` §۴: پیش‌نمایش اثر مالی).
 *
 * نرخ کمیسیون **همان نرخ Snapshot‌شده روی ردیف سفارش** است، نه نرخ ساری
 * امروز — بازگشت وجه هرگز کمیسیون را دوباره حساب نمی‌کند.
 */
final readonly class RefundEffect
{
    public function __construct(
        public Money $walletCredit,
        public Money $vendorPayableDebit,
        public Money $platformRevenueDebit,
    ) {}
}
