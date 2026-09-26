<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Commerce\Domain\PayoutRequest;
use App\Support\Audit\AuditEntry;

final readonly class PayoutCancelled implements AuditableEvent
{
    public function __construct(public PayoutRequest $payout) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'commerce.payout_cancelled',
            subjectType: PayoutRequest::class,
            subjectId: $this->payout->uuid,
            actorId: $this->payout->user_id,
            after: ['status' => $this->payout->status->value],
        );
    }
}
