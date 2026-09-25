<?php

declare(strict_types=1);

namespace App\Modules\Admin\Events;

use App\Contracts\AuditableEvent;
use App\Models\User;
use App\Support\Audit\AuditEntry;

/**
 * ایمیل مدیر از خط فرمان تأیید شد و ورود با کد ایمیلی برایش باز شد.
 *
 * خود ایمیل در دفتر نمی‌آید (داده شخصی)؛ فقط شناسه حساب.
 */
final readonly class AdminEmailLoginEnabled implements AuditableEvent
{
    public function __construct(public User $subject) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'admin.email_login_enabled',
            subjectType: User::class,
            subjectId: $this->subject->getKey(),
        );
    }
}
