<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Events;

use App\Contracts\AuditableEvent;
use App\Support\Audit\AuditEntry;

/**
 * یک فایل CSV روی بانک مواد اجرا شد.
 *
 * دفتر رویداد باید بگوید چه کسی و چند ماده را عوض کرد؛ خودِ فایل CSV ذخیره
 * نمی‌شود، چون داده شخصی نیست ولی حجمش برای دفتر رویداد مناسب نیست.
 */
final readonly class SubstancesImported implements AuditableEvent
{
    /** @param  list<string>  $updatedCasNumbers */
    public function __construct(
        public int $created,
        public int $updated,
        public array $updatedCasNumbers,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'chemicals.substances_imported',
            subjectType: 'csv_import',
            subjectId: null,
            actorId: $this->actorId,
            after: [
                'created' => $this->created,
                'updated' => $this->updated,
                'updated_cas_numbers' => $this->updatedCasNumbers,
            ],
        );
    }
}
