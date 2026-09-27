<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Jobs\Domain\Passport;
use App\Support\Audit\AuditEntry;

/** کارجو به بانک رزومه پیوست یا از آن بیرون آمد (Opt-in). */
final readonly class BankMembershipChanged implements AuditableEvent
{
    public function __construct(
        public Passport $passport,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: $this->passport->in_bank ? 'jobs.bank_joined' : 'jobs.bank_left',
            subjectType: Passport::class,
            subjectId: $this->passport->id,
            actorId: $this->passport->user_id,
            after: ['in_bank' => $this->passport->in_bank],
        );
    }
}
