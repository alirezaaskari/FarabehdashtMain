<?php

declare(strict_types=1);

namespace App\Support\Ledger;

use App\Support\Money;
use DateTimeInterface;

/**
 * یک ردیف گردش کیف پول.
 *
 * `direction` از دید کیف پول است: بستانکار یعنی پول به کیف پول آمد.
 */
final readonly class WalletLine
{
    public function __construct(
        public string $reference,
        public DateTimeInterface $occurredAt,
        public string $description,
        public EntryDirection $direction,
        public Money $amount,
    ) {}

    public function isCredit(): bool
    {
        return $this->direction === EntryDirection::Credit;
    }
}
