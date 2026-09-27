<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Events;

use App\Contracts\AuditableEvent;
use App\Support\Audit\AuditEntry;
use App\Support\Money;

final readonly class GatewaySettled implements AuditableEvent
{
    public function __construct(
        public string $transactionUuid,
        public Money $amount,
        public int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'ledger.gateway_settled',
            subjectType: 'ledger_transaction',
            subjectId: $this->transactionUuid,
            actorId: $this->actorId,
            context: ['amount_toman' => $this->amount->toman],
        );
    }
}
