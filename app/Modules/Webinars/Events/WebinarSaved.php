<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Webinars\Domain\Webinar;
use App\Support\Audit\AuditEntry;

/** پیوند جلسه عمداً در دفتر رویداد نوشته نمی‌شود. */
final readonly class WebinarSaved implements AuditableEvent
{
    /** @param  array<string, int|string>|null  $before */
    public function __construct(
        public Webinar $webinar,
        public ?array $before,
        public int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: $this->before === null ? 'webinars.webinar_created' : 'webinars.webinar_updated',
            subjectType: Webinar::class,
            subjectId: $this->webinar->uuid,
            actorId: $this->actorId,
            before: $this->before ?? [],
            after: [
                'starts_at' => $this->webinar->starts_at->toIso8601String(),
                'price_toman' => $this->webinar->price_toman,
                'capacity' => $this->webinar->capacity,
            ],
            context: ['slug' => $this->webinar->slug],
        );
    }
}
