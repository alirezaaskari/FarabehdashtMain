<?php

declare(strict_types=1);

namespace App\Support\Escrow;

use App\Support\Ledger\AccountType;
use App\Support\Money;
use Carbon\CarbonImmutable;

/**
 * وضعیت یک امانت، بدون افشای مدل ماژول دفتر کل (قاعده ۱).
 */
final readonly class EscrowHold
{
    public function __construct(
        public string $uuid,
        public string $key,
        public int $payerUserId,
        public int $payeeUserId,
        public Money $amount,
        public Money $commission,
        public EscrowStatus $status,
        public Money $refunded,
        public CarbonImmutable $heldAt,
        public ?CarbonImmutable $closedAt,
        public AccountType $account = AccountType::ServiceEscrow,
    ) {}

    /** سهم ارائه‌دهنده اگر همه پول آزاد شود. */
    public function payeeShare(): Money
    {
        return $this->amount->minus($this->commission);
    }
}
