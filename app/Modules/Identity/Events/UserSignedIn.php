<?php

declare(strict_types=1);

namespace App\Modules\Identity\Events;

use App\Contracts\AuditableEvent;
use App\Models\User;
use App\Support\Audit\AuditEntry;

/**
 * ورود موفق به حساب.
 *
 * ثبت می‌شود تا کاربر و مدیر بتوانند ورودهای ناشناس را ببینند. شماره موبایل
 * در ردیف نوشته نمی‌شود؛ شناسه کاربر برای پیگیری کافی است و ردیف رویداد
 * نباید به انبار داده شخصی تبدیل شود.
 */
final readonly class UserSignedIn implements AuditableEvent
{
    public function __construct(
        public User $user,
        public bool $accountWasCreated,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: $this->accountWasCreated ? 'user.registered' : 'user.signed_in',
            subjectType: User::class,
            subjectId: $this->user->getKey(),
            actorId: $this->user->getKey(),
            context: ['method' => 'otp'],
        );
    }
}
