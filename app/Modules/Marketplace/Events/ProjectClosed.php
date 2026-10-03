<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Marketplace\Domain\MarketProject;
use App\Support\Audit\AuditEntry;

/** کارفرما پروژه را پیش از قرارداد بست. */
final readonly class ProjectClosed implements AuditableEvent
{
    public function __construct(
        public MarketProject $project,
        public int $userId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'marketplace.project_closed',
            subjectType: MarketProject::class,
            subjectId: $this->project->uuid,
            actorId: $this->userId,
            after: ['status' => $this->project->status->value],
        );
    }
}
