<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Jobs\Domain\Enums\ApplicationStatus;
use App\Modules\Jobs\Domain\JobApplication;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * وضعیت درخواست عوض شد. تصمیم کارفرما به کارجو خبر داده می‌شود و پس‌گرفتن
 * کارجو به کارفرما.
 */
final readonly class ApplicationStatusChanged implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public JobApplication $application,
        public ApplicationStatus $previous,
        public int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'jobs.application_status',
            subjectType: JobApplication::class,
            subjectId: $this->application->uuid,
            actorId: $this->actorId,
            before: ['status' => $this->previous->value],
            after: ['status' => $this->application->status->value],
        );
    }

    public function userNotices(): array
    {
        $posting = $this->application->posting;

        if ($this->application->status === ApplicationStatus::Withdrawn) {
            return [new UserNotice(
                recipientId: $this->application->employerId(),
                kind: 'jobs.application_withdrawn',
                title: 'یک درخواست «'.$posting->title.'» پس گرفته شد',
                routeName: 'jobs.employer.applicants.index',
                routeParameters: ['uuid' => $posting->uuid],
            )];
        }

        return [new UserNotice(
            recipientId: $this->application->user_id,
            kind: 'jobs.application_status',
            title: 'درخواست شما برای «'.$posting->title.'»: '.$this->application->status->label(),
            routeName: 'jobs.applications.show',
            routeParameters: ['uuid' => $this->application->uuid],
        )];
    }
}
