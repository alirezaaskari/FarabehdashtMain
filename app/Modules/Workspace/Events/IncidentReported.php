<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Workspace\Domain\ServiceIncident;
use App\Support\Audit\AuditEntry;

/**
 * مدیر اختلال یا نگهداری تازه‌ای روی صفحه وضعیت ثبت کرد.
 */
final readonly class IncidentReported implements AuditableEvent
{
    public function __construct(
        public ServiceIncident $incident,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'workspace.incident_reported',
            subjectType: ServiceIncident::class,
            subjectId: $this->incident->uuid,
            actorId: $this->actorId,
            after: [
                'service' => $this->incident->service->value,
                'state' => $this->incident->state->value,
                'started_at' => $this->incident->started_at->toIso8601String(),
            ],
        );
    }
}
