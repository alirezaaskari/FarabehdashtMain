<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Jobs\Domain\Passport;
use App\Support\Audit\AuditEntry;

/**
 * کاربر گذرنامه‌اش را عوض کرد. فقط نوع تغییر و وضعیت اشتراک ثبت می‌شود، نه متن.
 */
final readonly class PassportUpdated implements AuditableEvent
{
    public function __construct(
        public Passport $passport,
        public string $change,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'jobs.passport_'.$this->change,
            subjectType: Passport::class,
            subjectId: $this->passport->id,
            actorId: $this->passport->user_id,
            after: ['shared' => $this->passport->shared],
        );
    }
}
