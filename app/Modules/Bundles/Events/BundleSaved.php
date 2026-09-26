<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Bundles\Domain\Bundle;
use App\Support\Audit\AuditEntry;

final readonly class BundleSaved implements AuditableEvent
{
    public function __construct(
        public Bundle $bundle,
        public ?int $priceBefore,
        public int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: $this->priceBefore === null ? 'bundles.bundle_created' : 'bundles.bundle_updated',
            subjectType: Bundle::class,
            subjectId: $this->bundle->uuid,
            actorId: $this->actorId,
            before: $this->priceBefore === null ? [] : ['price_toman' => $this->priceBefore],
            after: ['price_toman' => $this->bundle->price_toman, 'items' => $this->bundle->items->map->key()->all()],
            context: ['slug' => $this->bundle->slug],
        );
    }
}
