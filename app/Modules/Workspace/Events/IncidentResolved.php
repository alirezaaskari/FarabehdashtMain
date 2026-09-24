<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Workspace\Domain\ServiceIncident;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * رویداد اختلال رفع شد.
 *
 * هر کسی که «خبرم کن» زده بود یک اعلان می‌گیرد — از همان مسیر عمومی
 * اعلان‌ها، نه یک راه ویژه.
 */
final readonly class IncidentResolved implements AuditableEvent, UserNotifiableEvent
{
    /** @param  list<int>  $subscriberIds */
    public function __construct(
        public ServiceIncident $incident,
        public array $subscriberIds,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'workspace.incident_resolved',
            subjectType: ServiceIncident::class,
            subjectId: $this->incident->uuid,
            actorId: $this->actorId,
            after: ['resolved_at' => $this->incident->resolved_at?->toIso8601String()],
            context: ['subscribers' => count($this->subscriberIds)],
        );
    }

    public function userNotices(): array
    {
        return array_map(
            fn (int $userId): UserNotice => new UserNotice(
                recipientId: $userId,
                kind: 'workspace.incident_resolved',
                title: sprintf('«%s» دوباره برقرار است', $this->incident->service->label()),
                body: $this->incident->resolution,
                routeName: 'workspace.status',
            ),
            $this->subscriberIds,
        );
    }
}
