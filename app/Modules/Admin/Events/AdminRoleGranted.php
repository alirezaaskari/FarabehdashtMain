<?php

declare(strict_types=1);

namespace App\Modules\Admin\Events;

use App\Contracts\AuditableEvent;
use App\Models\User;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Support\Audit\AuditEntry;

/**
 * مدیر ارشد یک نقش مدیریتی اعطا کرد.
 *
 * از حساس‌ترین رویدادهای سیستم: از این لحظه یک حساب تازه می‌تواند محتوا منتشر
 * کند یا به پول دست بزند.
 */
final readonly class AdminRoleGranted implements AuditableEvent
{
    public function __construct(
        public User $subject,
        public AdminRole $role,
        public ?int $grantedBy,
        public ?string $note = null,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'admin.role_granted',
            subjectType: User::class,
            subjectId: $this->subject->getKey(),
            actorId: $this->grantedBy,
            after: ['role' => $this->role->value],
            context: array_filter([
                'role_label' => $this->role->label(),
                'note' => $this->note,
            ], static fn (?string $value): bool => $value !== null),
        );
    }
}
