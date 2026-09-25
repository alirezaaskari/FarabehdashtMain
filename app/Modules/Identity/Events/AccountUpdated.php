<?php

declare(strict_types=1);

namespace App\Modules\Identity\Events;

use App\Contracts\AuditableEvent;
use App\Models\User;
use App\Support\Audit\AuditEntry;

/**
 * کاربر نام یا ایمیلش را عوض کرد. دفتر رویداد فقط نام فیلدها را می‌نویسد، نه
 * مقدارشان — ایمیل داده شخصی است.
 */
final readonly class AccountUpdated implements AuditableEvent
{
    /** @param  list<string>  $fields */
    public function __construct(
        public User $user,
        public array $fields,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'identity.account_updated',
            subjectType: User::class,
            subjectId: $this->user->getKey(),
            actorId: (int) $this->user->getKey(),
            after: ['fields' => $this->fields],
        );
    }
}
