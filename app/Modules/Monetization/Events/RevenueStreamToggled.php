<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Monetization\Domain\Enums\ShutdownPolicy;
use App\Modules\Monetization\Domain\RevenueStreamToggle;
use App\Support\Audit\AuditEntry;

/**
 * مدیر یک جریان درآمدی را روشن یا خاموش کرد.
 *
 * قاعده ثابت سند کلیدها: هر تغییر وضعیت یک ردیف دفتر رویداد می‌سازد. این
 * تنها ردی است که بعداً می‌گوید چرا یک روز درآمد قطع شد.
 */
final readonly class RevenueStreamToggled implements AuditableEvent
{
    public function __construct(
        public RevenueStream $stream,
        public bool $wasEnabled,
        public bool $isEnabled,
        public ShutdownPolicy $policy,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'monetization.revenue_stream_toggled',
            subjectType: RevenueStreamToggle::class,
            subjectId: $this->stream->value,
            actorId: $this->actorId,
            before: ['is_enabled' => $this->wasEnabled],
            after: ['is_enabled' => $this->isEnabled, 'shutdown_policy' => $this->policy->value],
        );
    }
}
