<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Marketplace\Domain\Enums\ReviewMode;
use App\Support\Audit\AuditEntry;

/** مدیر حالت بررسی پیام یا فهرست کلمه‌ها را عوض کرد. */
final readonly class MessagePolicyChanged implements AuditableEvent
{
    public function __construct(
        public ReviewMode $before,
        public ReviewMode $after,
        public int $words,
        public int $adminId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'marketplace.message_policy_changed',
            subjectType: 'marketplace.messages',
            subjectId: 'policy',
            actorId: $this->adminId,
            before: ['mode' => $this->before->value],
            after: ['mode' => $this->after->value, 'words' => $this->words],
        );
    }
}
