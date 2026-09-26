<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Services;

use App\Contracts\LedgerBalanceReader;
use App\Modules\Commerce\Domain\PayoutRequest;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Money;

/**
 * قاعده‌های تسویه (DEC-45) در یک جا: کمترین مبلغ، مانده قابل‌تسویه و
 * درخواست باز. صفحه فروشنده، صفحه پنل و اکشن‌ها همه از همین می‌پرسند.
 */
final readonly class Payouts
{
    public function __construct(
        private LedgerBalanceReader $balances,
        private int $minimumToman,
    ) {}

    public function minimum(): Money
    {
        return Money::toman($this->minimumToman);
    }

    /** بدهی فعلی پلتفرم به این فروشنده/مدرس، طبق دفتر کل. */
    public function owed(int $userId): Money
    {
        return $this->balances->balanceOf(LedgerAccountRef::vendorPayable($userId));
    }

    public function openRequest(int $userId): ?PayoutRequest
    {
        return PayoutRequest::query()->where('user_id', $userId)->open()->first();
    }

    public function canRequest(int $userId): bool
    {
        return ! $this->owed($userId)->isLessThan($this->minimum()) && $this->openRequest($userId) === null;
    }
}
