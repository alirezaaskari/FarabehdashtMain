<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Jobs\Domain\Enums\PostingState;
use App\Modules\Jobs\Domain\JobPosting;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * مدیر درباره آگهی تصمیم گرفت. پس از تأیید، آگهی یا همان لحظه منتشر شده
 * (رایگان، یا ویرایش آگهی زنده) یا منتظر پرداخت کارفرماست.
 */
final readonly class PostingReviewed implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public JobPosting $posting,
        public int $adminId,
        public bool $approved,
        public int $recipientId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: $this->approved ? 'jobs.posting_approved' : 'jobs.posting_rejected',
            subjectType: JobPosting::class,
            subjectId: $this->posting->uuid,
            actorId: $this->adminId,
            after: ['status' => $this->posting->status->value, 'state' => $this->posting->state()->value],
        );
    }

    public function userNotices(): array
    {
        $state = $this->posting->state();

        return [new UserNotice(
            recipientId: $this->recipientId,
            kind: $this->approved ? 'jobs.posting_approved' : 'jobs.posting_rejected',
            title: match (true) {
                ! $this->approved => 'آگهی «'.$this->posting->title.'» برای اصلاح برگشت',
                $state === PostingState::AwaitingPayment => 'آگهی تأیید شد و آماده پرداخت است',
                default => 'آگهی «'.$this->posting->title.'» منتشر شد',
            },
            body: $this->approved ? null : (string) $this->posting->review_note,
            routeName: 'jobs.employer.postings.index',
        )];
    }
}
