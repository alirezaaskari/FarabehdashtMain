<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Marketplace\Domain\MarketProject;
use App\Support\Audit\AuditEntry;

/** کارفرما پروژه یا اصلاح آن را برای تأیید فرستاد. */
final readonly class ProjectSubmitted implements AuditableEvent
{
    public function __construct(
        public MarketProject $project,
        public int $userId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'marketplace.project_submitted',
            subjectType: MarketProject::class,
            subjectId: $this->project->uuid,
            actorId: $this->userId,
            after: ['status' => $this->project->status->value],
            context: ['service' => $this->project->service, 'private' => $this->project->is_private],
        );
    }
}
