<?php

declare(strict_types=1);

namespace App\Modules\Identity\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Identity\Domain\Enums\ProfileStatus;
use App\Modules\Identity\Domain\UserProfile;
use App\Support\Audit\AuditEntry;

/**
 * کاربر خودش یک نقش را غیرفعال کرد.
 *
 * هیچ داده‌ای حذف نشده؛ ردیف رویداد همین را ثبت می‌کند تا بعداً «محتوایم گم
 * شد» با واقعیت سنجیده شود.
 */
final readonly class ProfileDeactivated implements AuditableEvent
{
    public function __construct(
        public UserProfile $profile,
        public ProfileStatus $previousStatus,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'profile.deactivated',
            subjectType: UserProfile::class,
            subjectId: $this->profile->getKey(),
            actorId: $this->profile->user_id,
            before: ['status' => $this->previousStatus->value],
            after: ['status' => $this->profile->status->value],
            context: ['type' => $this->profile->type->value],
        );
    }
}
