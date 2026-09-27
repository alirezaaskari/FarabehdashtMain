<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Jobs\Domain\JobApplication;
use App\Support\Audit\AuditEntry;

/**
 * کارجو اجازه دیدن شماره و ایمیلش را برای یک درخواست داد یا پس گرفت (DEC-68).
 */
final readonly class ApplicationConsentChanged implements AuditableEvent
{
    public function __construct(public JobApplication $application) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'jobs.application_consent',
            subjectType: JobApplication::class,
            subjectId: $this->application->uuid,
            actorId: $this->application->user_id,
            before: ['share_contact' => ! $this->application->share_contact],
            after: ['share_contact' => $this->application->share_contact],
        );
    }
}
