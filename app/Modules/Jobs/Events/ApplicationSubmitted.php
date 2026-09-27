<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Jobs\Domain\JobApplication;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * کارجو برای آگهی درخواست فرستاد؛ کارفرما خبر می‌گیرد. نام کارجو در اعلان نمی‌آید.
 */
final readonly class ApplicationSubmitted implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(public JobApplication $application) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'jobs.application_submitted',
            subjectType: JobApplication::class,
            subjectId: $this->application->uuid,
            actorId: $this->application->user_id,
            after: ['share_contact' => $this->application->share_contact],
            context: ['posting' => $this->application->posting->uuid],
        );
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->application->employerId(),
            kind: 'jobs.application_received',
            title: 'درخواست تازه برای «'.$this->application->posting->title.'»',
            routeName: 'jobs.employer.applicants.show',
            routeParameters: ['uuid' => $this->application->uuid],
        )];
    }
}
