<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Jobs\Domain\JobPosting;
use App\Support\Audit\AuditEntry;

/** کارفرما آگهی را پیش از پایان اعتبار بست؛ روزهای مانده پس داده نمی‌شود. */
final readonly class PostingClosed implements AuditableEvent
{
    public function __construct(
        public JobPosting $posting,
        public int $userId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'jobs.posting_closed',
            subjectType: JobPosting::class,
            subjectId: $this->posting->uuid,
            actorId: $this->userId,
            context: ['expires_at' => $this->posting->expires_at?->toIso8601String()],
        );
    }
}
