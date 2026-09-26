<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Bundles\Domain\BundlePurchase;
use App\Support\Audit\AuditEntry;

final readonly class BundlePurchased implements AuditableEvent
{
    public function __construct(public BundlePurchase $purchase) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'bundles.purchased',
            subjectType: BundlePurchase::class,
            subjectId: $this->purchase->uuid,
            actorId: $this->purchase->user_id,
            after: [
                'price_toman' => $this->purchase->price_toman,
                'payment_source' => $this->purchase->payment_source->value,
            ],
            context: ['bundle_id' => $this->purchase->bundle_id],
        );
    }
}
