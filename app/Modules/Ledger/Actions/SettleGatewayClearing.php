<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Actions;

use App\Contracts\LedgerRecorder;
use App\Modules\Ledger\Domain\LedgerTransaction;
use App\Modules\Ledger\Events\GatewaySettled;
use App\Modules\Ledger\Services\LedgerService;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Ledger\LedgerEntryLine;
use App\Support\Ledger\LedgerReceipt;
use App\Support\Ledger\LedgerTransactionRequest;
use App\Support\Money;
use DomainException;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * ثبت رسیدن پول درگاه به حساب بانکی (بخش ۱۹-۱).
 *
 * پرداخت درگاه حساب واسط را بدهکار می‌کند (زرین‌پال به ما بدهکار است)؛ وقتی
 * زرین‌پال واریز کرد، همان مبلغ از حساب واسط به خزانه می‌رود. شماره پیگیری
 * واریز کلید idempotency است، پس ثبت دوباره همان واریز اثر دوم ندارد.
 */
final readonly class SettleGatewayClearing
{
    public function __construct(
        private LedgerRecorder $ledger,
        private LedgerService $balances,
        private Dispatcher $events,
    ) {}

    /** مبلغی که درگاه هنوز واریز نکرده. */
    public function outstanding(): Money
    {
        return $this->balances->debitBalanceOf(self::clearing());
    }

    public function handle(Money $amount, string $bankReference, int $actorId): LedgerReceipt
    {
        $reference = trim($bankReference);

        if ($amount->isZero() || $reference === '') {
            throw new DomainException('مبلغ و شماره پیگیری واریز لازم است.');
        }

        $key = 'ledger.gateway_settled:'.mb_strtolower($reference);
        $existing = LedgerTransaction::query()->where('idempotency_key', $key)->value('uuid');

        if (is_string($existing)) {
            return new LedgerReceipt($existing, alreadyRecorded: true);
        }

        if ($amount->isGreaterThan($this->outstanding())) {
            throw new DomainException('مبلغ از پول واریزنشده درگاه ('.$this->outstanding()->format().') بیشتر است.');
        }

        $receipt = $this->ledger->record(new LedgerTransactionRequest(
            kind: 'ledger.gateway_settled',
            idempotencyKey: $key,
            entries: [
                new LedgerEntryLine(new LedgerAccountRef(AccountType::Treasury), EntryDirection::Debit, $amount),
                new LedgerEntryLine(self::clearing(), EntryDirection::Credit, $amount),
            ],
            memo: 'واریز درگاه، پیگیری '.$reference,
            createdBy: $actorId,
        ));

        if (! $receipt->alreadyRecorded) {
            $this->events->dispatch(new GatewaySettled($receipt->transactionUuid, $amount, $actorId));
        }

        return $receipt;
    }

    private static function clearing(): LedgerAccountRef
    {
        return new LedgerAccountRef(AccountType::GatewayClearing);
    }
}
