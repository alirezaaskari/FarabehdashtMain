<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Events;

use App\Contracts\AuditableEvent;
use App\Support\Audit\AuditEntry;

final readonly class CommissionRateChanged implements AuditableEvent
{
    public function __construct(
        public string $flow,
        public int $beforeBp,
        public int $afterBp,
        public string $effectiveFrom,
        public int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'commerce.commission_rate_set',
            actorId: $this->actorId,
            before: ['rate_bp' => $this->beforeBp],
            after: ['rate_bp' => $this->afterBp, 'effective_from' => $this->effectiveFrom],
            context: ['flow' => $this->flow],
        );
    }
}
