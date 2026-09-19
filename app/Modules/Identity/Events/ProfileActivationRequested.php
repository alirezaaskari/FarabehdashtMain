<?php

declare(strict_types=1);

namespace App\Modules\Identity\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Identity\Domain\UserProfile;
use App\Support\Audit\AuditEntry;

/**
 * کاربر فعال‌سازی یک نقش تجاری را خواست.
 */
final readonly class ProfileActivationRequested implements AuditableEvent
{
    public function __construct(public UserProfile $profile) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'profile.activation_requested',
            subjectType: UserProfile::class,
            subjectId: $this->profile->getKey(),
            actorId: $this->profile->user_id,
            after: ['status' => $this->profile->status->value],
            context: ['type' => $this->profile->type->value],
        );
    }
}
