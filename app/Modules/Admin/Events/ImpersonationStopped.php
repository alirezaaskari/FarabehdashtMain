<?php

declare(strict_types=1);

namespace App\Modules\Admin\Events;

use App\Contracts\AuditableEvent;
use App\Models\User;
use App\Support\Audit\AuditEntry;

/** مدیر از حالت مشاهده‌به‌عنوان کاربر بیرون آمد. */
final readonly class ImpersonationStopped implements AuditableEvent
{
    public function __construct(
        public User $target,
        public int $adminId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'admin.impersonation_stopped',
            subjectType: User::class,
            subjectId: $this->target->getKey(),
            actorId: $this->adminId,
        );
    }
}
