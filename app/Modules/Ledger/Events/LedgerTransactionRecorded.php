<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Ledger\Domain\LedgerEntry;
use App\Modules\Ledger\Domain\LedgerTransaction;
use App\Support\Audit\AuditEntry;
use App\Support\Ledger\AccountType;
use App\Support\Notifications\UserNotice;

/**
 * یک تراکنش تازه در دفتر کل ثبت شد.
 *
 * فقط روی نوشتن **تازه** منتشر می‌شود؛ بازپخش یک درخواست با کلید تکراری
 * (`alreadyRecorded`) هیچ رویدادی نمی‌فرستد — وگرنه دفتر رویداد برای یک
 * تراکنش مالی چند بار «ثبت شد» می‌نوشت.
 *
 * مبلغ ردیف‌ها را می‌نویسد، ولی مالک حساب‌ها را نه: مبلغ داده حساس شخصی
 * نیست، شناسه کاربر پشت حساب هست.
 *
 * صاحب هر کیف پولی که در تراکنش حرکت کرده یک اعلان می‌گیرد: پول بدون خبر
 * صاحبش جابه‌جا نمی‌شود.
 */
final readonly class LedgerTransactionRecorded implements AuditableEvent, UserNotifiableEvent
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

    public function userNotices(): array
    {
        $this->transaction->loadMissing('entries.account');

        $notices = [];

        foreach ($this->transaction->entries as $entry) {
            $account = $entry->account;

            if ($account->type !== AccountType::UserWallet || $account->owner_id === null) {
                continue;
            }

            $notices[] = new UserNotice(
                recipientId: $account->owner_id,
                kind: $entry->direction->sign() > 0 ? 'ledger.wallet_credited' : 'ledger.wallet_debited',
                title: $entry->direction->sign() > 0
                    ? sprintf('%s به کیف پول شما اضافه شد', $entry->amount()->format())
                    : sprintf('%s از کیف پول شما برداشت شد', $entry->amount()->format()),
                routeName: 'workspace.wallet',
            );
        }

        return $notices;
    }
}
