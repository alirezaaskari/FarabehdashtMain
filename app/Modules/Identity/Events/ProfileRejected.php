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
 * مدیر یک درخواست نقش را رد کرد.
 *
 * یادداشت مدیر بخشی از ردیف رویداد است: اگر کاربر بعداً اعتراض کند، باید
 * بشود دقیقاً دید چه گفته شده.
 */
final readonly class ProfileRejected implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public UserProfile $profile,
        public ProfileStatus $previousStatus,
        public int $adminId,
        public string $note,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'profile.rejected',
            subjectType: UserProfile::class,
            subjectId: $this->profile->getKey(),
            actorId: $this->adminId,
            before: ['status' => $this->previousStatus->value],
            after: ['status' => $this->profile->status->value],
            context: [
                'type' => $this->profile->type->value,
                'user_id' => $this->profile->user_id,
                'note' => $this->note,
            ],
        );
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->profile->user_id,
            kind: 'profile.rejected',
            title: sprintf('درخواست نقش «%s» تأیید نشد', $this->profile->type->label()),
            body: $this->note,
            routeName: 'identity.profiles',
        )];
    }
}
