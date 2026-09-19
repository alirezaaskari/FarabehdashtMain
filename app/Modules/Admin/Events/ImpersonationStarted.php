<?php

declare(strict_types=1);

namespace App\Modules\Admin\Events;

use App\Contracts\AuditableEvent;
use App\Models\User;
use App\Support\Audit\AuditEntry;

/**
 * مدیر میزکار را از چشم یک کاربر دید.
 *
 * ثبت این رویداد **اجباری** است و نه اختیاری: دیدن میزکار کاربر یعنی دیدن
 * داده شخصی او، و باید همیشه ردی از آن بماند.
 */
final readonly class ImpersonationStarted implements AuditableEvent
{
    public function __construct(
        public User $target,
        public int $adminId,
        public string $reason,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'admin.impersonation_started',
            subjectType: User::class,
            subjectId: $this->target->getKey(),
            actorId: $this->adminId,
            context: ['reason' => $this->reason],
        );
    }
}
