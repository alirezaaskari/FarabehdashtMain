<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Bundles\Domain\Bundle;
use App\Modules\Bundles\Domain\Enums\BundleStatus;
use App\Support\Audit\AuditEntry;

final readonly class BundleStatusChanged implements AuditableEvent
{
    public function __construct(
        public Bundle $bundle,
        public BundleStatus $before,
        public int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'bundles.bundle_'.$this->bundle->status->value,
            subjectType: Bundle::class,
            subjectId: $this->bundle->uuid,
            actorId: $this->actorId,
            before: ['status' => $this->before->value],
            after: ['status' => $this->bundle->status->value],
        );
    }
}
