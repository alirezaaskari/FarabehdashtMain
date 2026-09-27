<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Jobs\Domain\Company;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * مدیر درباره ویرایش صفحه شرکت تصمیم گرفت: انتشار یا برگرداندن با یادداشت.
 */
final readonly class CompanyReviewed implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public Company $company,
        public int $adminId,
        public bool $approved,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: $this->approved ? 'jobs.company_published' : 'jobs.company_rejected',
            subjectType: Company::class,
            subjectId: $this->company->uuid,
            actorId: $this->adminId,
            after: ['status' => $this->company->status->value, 'slug' => $this->company->slug],
        );
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->company->user_id,
            kind: $this->approved ? 'jobs.company_published' : 'jobs.company_rejected',
            title: $this->approved ? 'صفحه شرکت شما تأیید شد' : 'صفحه شرکت برای اصلاح برگشت',
            body: $this->approved
                ? 'اکنون می‌توانید آگهی شغلی ثبت کنید.'
                : (string) $this->company->review_note,
            routeName: $this->approved ? 'jobs.employer.postings.create' : 'jobs.company.edit',
        )];
    }
}
