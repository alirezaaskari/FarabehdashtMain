<?php

declare(strict_types=1);

namespace App\Modules\Admin\Events;

use App\Contracts\AuditableEvent;
use App\Models\User;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Support\Audit\AuditEntry;

/** مدیر ارشد یک نقش مدیریتی را پس گرفت. */
final readonly class AdminRoleRevoked implements AuditableEvent
{
    public function __construct(
        public User $subject,
        public AdminRole $role,
        public ?int $revokedBy,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'admin.role_revoked',
            subjectType: User::class,
            subjectId: $this->subject->getKey(),
            actorId: $this->revokedBy,
            before: ['role' => $this->role->value],
            context: ['role_label' => $this->role->label()],
        );
    }
}
