<?php

declare(strict_types=1);

namespace App\Support\Ledger;

use App\Support\Money;

/**
 * یک ردیف پیشنهادی برای ثبت در دفتر کل — پیش از نوشته‌شدن.
 */
final readonly class LedgerEntryLine
{
    public function __construct(
        public LedgerAccountRef $account,
        public EntryDirection $direction,
        public Money $amount,
    ) {}
}
