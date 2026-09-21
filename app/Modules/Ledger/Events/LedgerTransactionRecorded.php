<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Ledger\Domain\LedgerEntry;
use App\Modules\Ledger\Domain\LedgerTransaction;
use App\Support\Audit\AuditEntry;

/**
 * یک تراکنش تازه در دفتر کل ثبت شد.
 *
 * فقط روی نوشتن **تازه** منتشر می‌شود؛ بازپخش یک درخواست با کلید تکراری
 * (`alreadyRecorded`) هیچ رویدادی نمی‌فرستد — وگرنه دفتر رویداد برای یک
 * تراکنش مالی چند بار «ثبت شد» می‌نوشت.
 *
 * مبلغ ردیف‌ها را می‌نویسد، ولی مالک حساب‌ها را نه: مبلغ داده حساس شخصی
 * نیست، شناسه کاربر پشت حساب هست.
 */
final readonly class LedgerTransactionRecorded implements AuditableEvent
{
    public function __construct(public LedgerTransaction $transaction) {}

    public function auditEntry(): AuditEntry
    {
        $this->transaction->loadMissing('entries');

        return new AuditEntry(
            action: 'ledger.transaction_recorded',
            subjectType: LedgerTransaction::class,
            subjectId: $this->transaction->uuid,
            actorId: $this->transaction->created_by,
            after: [
                'kind' => $this->transaction->kind,
                'reference_type' => $this->transaction->reference_type,
                'reference_id' => $this->transaction->reference_id,
                'entries' => $this->transaction->entries
                    ->map(static fn (LedgerEntry $entry): array => [
                        'direction' => $entry->direction->value,
                        'amount_toman' => $entry->amount_toman,
                    ])
                    ->all(),
            ],
        );
    }
}
