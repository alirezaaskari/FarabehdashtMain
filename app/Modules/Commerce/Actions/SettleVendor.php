<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Actions;

use App\Contracts\LedgerBalanceReader;
use App\Contracts\LedgerRecorder;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Ledger\LedgerEntryLine;
use App\Support\Ledger\LedgerReceipt;
use App\Support\Ledger\LedgerTransactionRequest;
use App\Support\Money;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * تسویه دستی یک فروشنده — پرداخت واقعی از خزانه به حساب بانکی فروشنده،
 * بیرون از این سیستم؛ این اکشن فقط اثرش را در دفتر کل ثبت می‌کند.
 *
 * بدون آستانه یا بازه زمانی خودکار: DEC-17 (حداقل/حداکثر مبلغ تسویه و بازه
 * زمانی‌اش) هنوز باز است. تا آن تصمیم، مدیر مالی هر زمان و هر مبلغی تا سقف
 * بدهی واقعی را دستی تسویه می‌کند — همان الگوی شارژ دستی کیف پول در بخش ۱۱.
 */
final readonly class SettleVendor
{
    public function __construct(
        private LedgerRecorder $ledger,
        private LedgerBalanceReader $balances,
    ) {}

    public function handle(int $vendorUserId, Money $amount, int $actorId, ?string $memo = null): LedgerReceipt
    {
        if ($amount->isZero()) {
            throw new InvalidArgumentException('مبلغ تسویه باید بزرگ‌تر از صفر باشد.');
        }

        $owed = $this->balances->balanceOf(LedgerAccountRef::vendorPayable($vendorUserId));

        if ($amount->isGreaterThan($owed)) {
            throw new InvalidArgumentException('مبلغ تسویه از بدهی فعلی به این فروشنده بیشتر است.');
        }

        return $this->ledger->record(new LedgerTransactionRequest(
            kind: 'commerce.vendor_settled',
            idempotencyKey: (string) Str::uuid7(),
            entries: [
                new LedgerEntryLine(LedgerAccountRef::vendorPayable($vendorUserId), EntryDirection::Debit, $amount),
                new LedgerEntryLine(new LedgerAccountRef(AccountType::Treasury), EntryDirection::Credit, $amount),
            ],
            referenceType: 'vendor',
            referenceId: (string) $vendorUserId,
            memo: $memo,
            createdBy: $actorId,
        ));
    }
}
