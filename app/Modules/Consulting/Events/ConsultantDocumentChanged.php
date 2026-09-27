<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Consulting\Domain\ConsultantDocument;
use App\Support\Audit\AuditEntry;

/**
 * مدرکی برای بررسی افزوده یا برداشته شد. نام فایل در دفتر رویداد نمی‌آید؛
 * ممکن است نام یا کد ملی در آن باشد.
 */
final readonly class ConsultantDocumentChanged implements AuditableEvent
{
    public function __construct(
        public ConsultantDocument $document,
        public int $userId,
        public bool $removed,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: $this->removed ? 'consulting.document_removed' : 'consulting.document_added',
            subjectType: ConsultantDocument::class,
            subjectId: $this->document->uuid,
            actorId: $this->userId,
            context: ['profile_id' => $this->document->profile_id, 'size_bytes' => $this->document->size_bytes],
        );
    }
}
