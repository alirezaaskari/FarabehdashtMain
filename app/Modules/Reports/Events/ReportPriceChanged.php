<?php

declare(strict_types=1);

namespace App\Modules\Reports\Events;

use App\Contracts\AuditableEvent;
use App\Support\Audit\AuditEntry;
use App\Support\Money;

final readonly class ReportPriceChanged implements AuditableEvent
{
    public function __construct(
        public Money $before,
        public Money $after,
        public int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'reports.price_changed',
            actorId: $this->actorId,
            before: ['price_toman' => $this->before->toman],
            after: ['price_toman' => $this->after->toman],
        );
    }
}
