<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Jobs\Domain\Company;
use App\Support\Audit\AuditEntry;

/** کارفرما صفحه شرکت را برای تأیید فرستاد؛ متنش در خود ردیف شرکت است. */
final readonly class CompanySubmitted implements AuditableEvent
{
    public function __construct(public Company $company) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'jobs.company_submitted',
            subjectType: Company::class,
            subjectId: $this->company->uuid,
            actorId: $this->company->user_id,
            after: ['status' => $this->company->status->value],
            context: ['first' => $this->company->published_at === null],
        );
    }
}
