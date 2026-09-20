<?php

declare(strict_types=1);

namespace App\Modules\Tools\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Tools\Domain\Tool;
use App\Support\Audit\AuditEntry;

/**
 * مدیر بازبینی علمی یک ابزار را ثبت کرد.
 *
 * تاریخ بازبینی ادعای اعتبار علمی است؛ باید معلوم باشد چه کسی و کی ثبتش کرده.
 */
final readonly class ToolReviewed implements AuditableEvent
{
    public function __construct(
        public string $slug,
        public string $reviewedAt,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'tools.reviewed',
            subjectType: Tool::class,
            subjectId: $this->slug,
            actorId: $this->actorId,
            after: ['reviewed_at' => $this->reviewedAt],
        );
    }
}
