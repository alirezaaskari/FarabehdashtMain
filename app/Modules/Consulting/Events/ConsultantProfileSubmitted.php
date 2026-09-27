<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Support\Audit\AuditEntry;

/**
 * مشاور صفحه‌اش را برای تأیید فرستاد. متن صفحه در دفتر رویداد نمی‌آید؛
 * نسخه‌اش در خود ردیف پروفایل هست.
 */
final readonly class ConsultantProfileSubmitted implements AuditableEvent
{
    public function __construct(public ConsultantProfile $profile) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'consulting.profile_submitted',
            subjectType: ConsultantProfile::class,
            subjectId: $this->profile->uuid,
            actorId: $this->profile->user_id,
            after: ['status' => $this->profile->status->value],
            context: ['first' => $this->profile->published_at === null],
        );
    }
}
