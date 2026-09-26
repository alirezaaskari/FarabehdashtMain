<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Commerce\Domain\PayoutRequest;
use App\Support\Audit\AuditEntry;

final readonly class PayoutRequested implements AuditableEvent
{
    public function __construct(public PayoutRequest $payout) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'commerce.payout_requested',
            subjectType: PayoutRequest::class,
            subjectId: $this->payout->uuid,
            actorId: $this->payout->user_id,
            after: ['amount_toman' => $this->payout->amount_toman, 'status' => $this->payout->status->value],
        );
    }
}
