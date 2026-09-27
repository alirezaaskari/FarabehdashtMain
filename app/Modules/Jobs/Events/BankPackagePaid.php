<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Jobs\Domain\BankPackage;
use App\Support\Audit\AuditEntry;

/** بسته درخواست تماس بانک رزومه پرداخت شد (DEC-72). */
final readonly class BankPackagePaid implements AuditableEvent
{
    public function __construct(
        public BankPackage $package,
        public int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'jobs.bank_package_paid',
            subjectType: BankPackage::class,
            subjectId: $this->package->uuid,
            actorId: $this->actorId,
            context: [
                'company' => $this->package->company_id,
                'credits' => $this->package->credits,
                'price_toman' => $this->package->price_toman,
                'source' => $this->package->payment_source?->value,
            ],
        );
    }
}
