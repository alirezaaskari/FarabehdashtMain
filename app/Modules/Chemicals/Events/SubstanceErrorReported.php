<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Chemicals\Domain\Substance;
use App\Modules\Chemicals\Domain\SubstanceErrorReport;
use App\Support\Audit\AuditEntry;

/** کاربری گزارش داد که در صفحه یک ماده اشتباهی هست. */
final readonly class SubstanceErrorReported implements AuditableEvent
{
    public function __construct(public SubstanceErrorReport $report) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'chemicals.error_reported',
            subjectType: Substance::class,
            subjectId: $this->report->substance->uuid,
            actorId: $this->report->user_id,
            after: ['report_id' => $this->report->id, 'topic' => $this->report->topic->value],
        );
    }
}
