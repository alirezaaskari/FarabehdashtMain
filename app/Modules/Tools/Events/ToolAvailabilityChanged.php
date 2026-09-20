<?php

declare(strict_types=1);

namespace App\Modules\Tools\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Tools\Domain\Tool;
use App\Support\Audit\AuditEntry;

/**
 * مدیر یک ابزار را روشن یا خاموش کرد.
 *
 * خاموش‌کردن ابزار یعنی کاربران دیگر نمی‌توانند با آن محاسبه کنند؛ باید رد
 * داشته باشد.
 */
final readonly class ToolAvailabilityChanged implements AuditableEvent
{
    public function __construct(
        public string $slug,
        public bool $wasEnabled,
        public bool $isEnabled,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'tools.availability_changed',
            subjectType: Tool::class,
            subjectId: $this->slug,
            actorId: $this->actorId,
            before: ['is_enabled' => $this->wasEnabled],
            after: ['is_enabled' => $this->isEnabled],
        );
    }
}
