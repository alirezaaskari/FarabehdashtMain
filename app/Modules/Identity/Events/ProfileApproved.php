<?php

declare(strict_types=1);

namespace App\Modules\Identity\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Identity\Domain\Enums\ProfileStatus;
use App\Modules\Identity\Domain\UserProfile;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * مدیر یک نقش تجاری را تأیید کرد.
 *
 * این یکی از حساس‌ترین رویدادهای سیستم است: از این لحظه کاربر می‌تواند
 * محتوا بفروشد یا آگهی بگذارد.
 */
final readonly class ProfileApproved implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public UserProfile $profile,
        public ProfileStatus $previousStatus,
        public int $adminId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'profile.approved',
            subjectType: UserProfile::class,
            subjectId: $this->profile->getKey(),
            actorId: $this->adminId,
            before: ['status' => $this->previousStatus->value],
            after: ['status' => $this->profile->status->value],
            context: [
                'type' => $this->profile->type->value,
                'user_id' => $this->profile->user_id,
            ],
        );
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->profile->user_id,
            kind: 'profile.approved',
            title: sprintf('نقش «%s» برای شما فعال شد', $this->profile->type->label()),
            body: 'نمای تازه‌ای در میزکار برای این نقش اضافه شد.',
            routeName: 'workspace.dashboard',
        )];
    }
}
