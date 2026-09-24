<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Workspace\Domain\LegalVersion;
use App\Support\Audit\AuditEntry;

/**
 * کاربر نسخه‌های جاری صفحات حقوقی را پذیرفت.
 *
 * پذیرش قرارداد است؛ ردیفش در دفتر رویداد هم می‌ماند تا اگر روزی جدول
 * پذیرش‌ها دست خورد، شاهد مستقلی باشد.
 */
final readonly class LegalVersionsAccepted implements AuditableEvent
{
    /** @param  list<LegalVersion>  $versions */
    public function __construct(
        public int $userId,
        public array $versions,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'workspace.legal_accepted',
            subjectType: null,
            subjectId: null,
            actorId: $this->userId,
            after: [
                'versions' => array_map(
                    static fn (LegalVersion $version): string => $version->uuid,
                    $this->versions,
                ),
            ],
        );
    }
}
