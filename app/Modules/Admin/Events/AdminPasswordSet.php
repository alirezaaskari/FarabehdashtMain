<?php

declare(strict_types=1);

namespace App\Modules\Admin\Events;

use App\Contracts\AuditableEvent;
use App\Models\User;
use App\Support\Audit\AuditEntry;

/**
 * رمز ورود مدیر از خط فرمان ثبت یا عوض شد. فقط شناسه حساب در دفتر می‌آید.
 */
final readonly class AdminPasswordSet implements AuditableEvent
{
    public function __construct(public User $subject) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'admin.password_set',
            subjectType: User::class,
            subjectId: $this->subject->getKey(),
        );
    }
}
