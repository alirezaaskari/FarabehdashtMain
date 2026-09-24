<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Workspace\Domain\LegalVersion;
use App\Support\Audit\AuditEntry;

/**
 * نسخه تازه‌ای از یک صفحه حقوقی منتشر شد.
 *
 * برای همه کاربران اعلان ساخته نمی‌شود: با هزاران کاربر، هزاران ردیف هم‌زمان
 * می‌شد. نسخه اساسی خودش در ورود بعدی هر کاربر به صفحه پذیرش می‌رسد.
 */
final readonly class LegalVersionPublished implements AuditableEvent
{
    public function __construct(
        public LegalVersion $version,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'workspace.legal_version_published',
            subjectType: LegalVersion::class,
            subjectId: $this->version->uuid,
            actorId: $this->actorId,
            after: [
                'document' => $this->version->document->value,
                'version' => $this->version->version,
                'change' => $this->version->change->value,
                'effective_at' => $this->version->effective_at->toIso8601String(),
            ],
        );
    }
}
