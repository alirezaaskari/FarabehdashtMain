<?php

declare(strict_types=1);

namespace App\Modules\Identity\Events;

use App\Contracts\AuditableEvent;
use App\Models\User;
use App\Support\Audit\AuditEntry;

/** کاربر از همه دستگاه‌های دیگرش خارج شد — نخستین کار پس از گم‌شدن گوشی. */
final readonly class OtherSessionsSignedOut implements AuditableEvent
{
    public function __construct(
        public User $user,
        public int $closedSessions,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'identity.other_sessions_signed_out',
            subjectType: User::class,
            subjectId: $this->user->getKey(),
            actorId: (int) $this->user->getKey(),
            after: ['closed_sessions' => $this->closedSessions],
        );
    }
}
