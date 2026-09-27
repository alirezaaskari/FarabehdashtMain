<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * مدیر ویرایش صفحه مشاور را با یادداشت برگرداند. نسخه منتشرشده قبلی، اگر
 * باشد، سر جایش می‌ماند.
 */
final readonly class ConsultantProfileRejected implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public ConsultantProfile $profile,
        public int $adminId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'consulting.profile_rejected',
            subjectType: ConsultantProfile::class,
            subjectId: $this->profile->uuid,
            actorId: $this->adminId,
            after: ['status' => $this->profile->status->value],
        );
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->profile->user_id,
            kind: 'consulting.profile_rejected',
            title: 'ویرایش صفحه مشاور برای اصلاح برگشت',
            body: $this->profile->review_note,
            routeName: 'consulting.profile.edit',
        )];
    }
}
