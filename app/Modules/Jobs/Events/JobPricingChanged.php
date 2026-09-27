<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Events;

use App\Contracts\AuditableEvent;
use App\Support\Audit\AuditEntry;

/** مدیر قیمت، مدت یا قاعده «اولین آگهی رایگان» را از پنل عوض کرد. */
final readonly class JobPricingChanged implements AuditableEvent
{
    /**
     * @param  array<string, int>  $before
     * @param  array<string, int>  $after
     */
    public function __construct(
        public array $before,
        public array $after,
        public int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'jobs.pricing_changed',
            actorId: $this->actorId,
            before: $this->before,
            after: $this->after,
        );
    }
}
