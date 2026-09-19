<?php

declare(strict_types=1);

namespace App\Modules\Identity\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Identity\Domain\Enums\ProfileStatus;
use App\Modules\Identity\Domain\UserProfile;
use App\Support\Audit\AuditEntry;

/**
 * مدیر یک نقش تجاری را تأیید کرد.
 *
 * این یکی از حساس‌ترین رویدادهای سیستم است: از این لحظه کاربر می‌تواند
 * محتوا بفروشد یا آگهی بگذارد.
 */
final readonly class ProfileApproved implements AuditableEvent
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
}
