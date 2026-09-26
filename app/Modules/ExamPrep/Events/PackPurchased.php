<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Events;

use App\Contracts\AuditableEvent;
use App\Modules\ExamPrep\Domain\PackPurchase;
use App\Support\Audit\AuditEntry;

final readonly class PackPurchased implements AuditableEvent
{
    public function __construct(public PackPurchase $purchase) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'exam_prep.pack_purchased',
            subjectType: PackPurchase::class,
            subjectId: $this->purchase->uuid,
            actorId: $this->purchase->user_id,
            after: [
                'price_toman' => $this->purchase->price_toman,
                'payment_source' => $this->purchase->payment_source->value,
            ],
            context: ['exam_pack_id' => $this->purchase->exam_pack_id],
        );
    }
}
