<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Jobs\Domain\JobPosting;
use App\Support\Audit\AuditEntry;

/** کارفرما آگهی یا ویرایش آگهی را برای تأیید فرستاد. */
final readonly class PostingSubmitted implements AuditableEvent
{
    public function __construct(
        public JobPosting $posting,
        public int $userId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'jobs.posting_submitted',
            subjectType: JobPosting::class,
            subjectId: $this->posting->uuid,
            actorId: $this->userId,
            after: ['status' => $this->posting->status->value],
            context: ['company_id' => $this->posting->company_id, 'first' => $this->posting->approved_at === null],
        );
    }
}
