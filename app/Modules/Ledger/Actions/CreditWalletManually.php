<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Actions;

use App\Contracts\LedgerRecorder;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Ledger\LedgerEntryLine;
use App\Support\Ledger\LedgerReceipt;
use App\Support\Ledger\LedgerTransactionRequest;
use App\Support\Money;
use Illuminate\Support\Str;

/**
 * شارژ دستی کیف پول — تنها راه ورود پول به سیستم در نسخه یک (ADR-0003).
 *
 * طرف مقابل حساب خزانه (`AccountType::Treasury`) است، نه یک حساب دیگر: پول
 * از بیرون سیستم (واریز بانکی، نقد) وارد می‌شود و باید از یک‌جا «بدهکار»
 * شود تا تراکنش صفر شود. جزئیات این تصمیم در DEC-20 است.
 */
final readonly class CreditWalletManually
{
    public function __construct(private LedgerRecorder $ledger) {}

    public function handle(
        int $userId,
        Money $amount,
        ?int $actorId,
        ?string $memo = null,
        ?string $idempotencyKey = null,
    ): LedgerReceipt {
        return $this->ledger->record(new LedgerTransactionRequest(
            kind: 'wallet.manual_topup',
            idempotencyKey: $idempotencyKey ?? (string) Str::uuid7(),
            entries: [
                new LedgerEntryLine(new LedgerAccountRef(AccountType::Treasury), EntryDirection::Debit, $amount),
                new LedgerEntryLine(LedgerAccountRef::wallet($userId), EntryDirection::Credit, $amount),
            ],
            memo: $memo,
            createdBy: $actorId,
        ));
    }
}
