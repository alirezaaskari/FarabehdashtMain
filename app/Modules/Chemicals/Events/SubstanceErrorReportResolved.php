<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Chemicals\Domain\Enums\ErrorReportStatus;
use App\Modules\Chemicals\Domain\Substance;
use App\Modules\Chemicals\Domain\SubstanceErrorReport;
use App\Support\Audit\AuditEntry;

/** مدیر گزارش اشتباهی را بست: اصلاح شد یا رد شد. */
final readonly class SubstanceErrorReportResolved implements AuditableEvent
{
    public function __construct(
        public SubstanceErrorReport $report,
        public int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'chemicals.error_report_resolved',
            subjectType: Substance::class,
            subjectId: $this->report->substance->uuid,
            actorId: $this->actorId,
            before: ['status' => ErrorReportStatus::Open->value],
            after: ['status' => $this->report->status->value, 'report_id' => $this->report->id],
        );
    }
}
