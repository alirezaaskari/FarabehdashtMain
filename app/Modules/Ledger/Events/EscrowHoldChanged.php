<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Ledger\Domain\EscrowHold;
use App\Support\Audit\AuditEntry;

/**
 * نگه‌داشتن، آزادسازی، بازگشت یا تقسیم یک امانت — فقط برای دفتر رویداد.
 * شناسه‌ها و مبلغ‌ها نوشته می‌شوند، نه داده شخصی.
 */
final readonly class EscrowHoldChanged implements AuditableEvent
{
    public function __construct(
        public EscrowHold $hold,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'ledger.escrow_'.$this->hold->status->value,
            subjectType: EscrowHold::class,
            subjectId: $this->hold->uuid,
            actorId: $this->actorId,
            after: ['status' => $this->hold->status->value],
            context: [
                'reference_type' => $this->hold->reference_type,
                'reference_id' => $this->hold->reference_id,
                'amount_toman' => $this->hold->amount_toman,
                'commission_toman' => $this->hold->commission_toman,
                'refunded_toman' => $this->hold->refunded_toman,
                'reason' => $this->hold->close_reason,
            ],
        );
    }
}
