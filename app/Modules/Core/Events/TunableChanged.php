<?php

declare(strict_types=1);

namespace App\Modules\Core\Events;

use App\Contracts\AuditableEvent;
use App\Support\Audit\AuditEntry;

/** مدیر ارشد یک قیمت یا مدت را از پنل «قیمت‌ها و زمان‌ها» عوض کرد. */
final readonly class TunableChanged implements AuditableEvent
{
    public function __construct(
        public string $key,
        public int $before,
        public int $after,
        public int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'settings.tunable_changed',
            actorId: $this->actorId,
            before: [$this->key => $this->before],
            after: [$this->key => $this->after],
        );
    }
}
