<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * مدیر ویرایش صفحه مشاور را تأیید کرد و نسخه تازه منتشر شد.
 */
final readonly class ConsultantProfilePublished implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public ConsultantProfile $profile,
        public int $adminId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'consulting.profile_published',
            subjectType: ConsultantProfile::class,
            subjectId: $this->profile->uuid,
            actorId: $this->adminId,
            after: ['status' => $this->profile->status->value, 'slug' => $this->profile->slug],
        );
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->profile->user_id,
            kind: 'consulting.profile_published',
            title: 'صفحه '.$this->profile->kind->label().' شما منتشر شد',
            body: 'ویرایش صفحه عمومی شما تأیید شد و اکنون همه آن را می‌بینند.',
            routeName: $this->profile->kind->route(),
            routeParameters: ['slug' => (string) $this->profile->slug],
        )];
    }
}
