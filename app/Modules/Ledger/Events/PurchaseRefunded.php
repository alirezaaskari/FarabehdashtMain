<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Events;

use App\Contracts\AuditableEvent;
use App\Support\Audit\AuditEntry;

/**
 * مدیر مالی خریدی را از صفحه «بازگشت وجه خریدهای دیگر» برگرداند. خبر واریز
 * به خریدار را خود دفتر کل می‌دهد؛ این رویداد برای دفتر رویداد است.
 */
final readonly class PurchaseRefunded implements AuditableEvent
{
    public function __construct(
        public string $kind,
        public string $uuid,
        public int $buyerId,
        public int $amountToman,
        public string $reason,
        public int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'ledger.purchase_refunded',
            subjectType: $this->kind,
            subjectId: $this->uuid,
            actorId: $this->actorId,
            after: ['status' => 'refunded'],
            context: [
                'buyer_user_id' => $this->buyerId,
                'amount_toman' => $this->amountToman,
                'reason' => $this->reason,
            ],
        );
    }
}
