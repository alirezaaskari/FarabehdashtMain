<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Webinars\Domain\Enums\WebinarStatus;
use App\Modules\Webinars\Domain\Webinar;
use App\Support\Audit\AuditEntry;

final readonly class WebinarStatusChanged implements AuditableEvent
{
    public function __construct(
        public Webinar $webinar,
        public WebinarStatus $before,
        public int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'webinars.webinar_'.$this->webinar->status->value,
            subjectType: Webinar::class,
            subjectId: $this->webinar->uuid,
            actorId: $this->actorId,
            before: ['status' => $this->before->value],
            after: ['status' => $this->webinar->status->value],
        );
    }
}
