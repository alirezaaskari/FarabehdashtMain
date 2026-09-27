<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Jobs\Domain\CompanyDocument;
use App\Support\Audit\AuditEntry;

/**
 * مدرک شرکت افزوده یا برداشته شد. نام فایل در دفتر رویداد نمی‌آید؛ ممکن
 * است شناسه ملی شرکت یا نام شخص در آن باشد.
 */
final readonly class CompanyDocumentChanged implements AuditableEvent
{
    public function __construct(
        public CompanyDocument $document,
        public int $userId,
        public bool $removed,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: $this->removed ? 'jobs.document_removed' : 'jobs.document_added',
            subjectType: CompanyDocument::class,
            subjectId: $this->document->uuid,
            actorId: $this->userId,
            context: ['company_id' => $this->document->company_id, 'size_bytes' => $this->document->size_bytes],
        );
    }
}
