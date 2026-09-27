<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Services;

use App\Contracts\EscrowKeeper;
use App\Contracts\LedgerRecorder;
use App\Modules\Ledger\Domain\EscrowHold;
use App\Modules\Ledger\Events\EscrowHoldChanged;
use App\Support\Escrow\EscrowHold as EscrowHoldData;
use App\Support\Escrow\EscrowHoldRequest;
use App\Support\Escrow\EscrowStatus;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Ledger\LedgerEntryLine;
use App\Support\Ledger\LedgerTransactionRequest;
use App\Support\Money;
use DomainException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * پیاده‌سازی {@see EscrowKeeper} روی دفتر کل.
 *
 * هر حرکت یک تراکنش با کلید `ledger.escrow_<کار>:<uuid امانت>` است؛ پس اجرای
 * دوباره (Job تکراری، دوبار کلیک) اثر مالی دوم ندارد. ردیف امانت پیش از هر
 * تغییر با `lockForUpdate` قفل می‌شود تا آزادسازی خودکار و رأی مدیر هم‌زمان
 * هر دو «باز» نبینند.
 */
final readonly class EscrowService implements EscrowKeeper
{
    public function __construct(
        private LedgerRecorder $ledger,
        private DatabaseManager $db,
        private Dispatcher $events,
    ) {}

    public function hold(EscrowHoldRequest $request): EscrowHoldData
    {
        if ($request->amount->isZero() || $request->commission->isGreaterThan($request->amount)) {
            throw new DomainException('مبلغ امانت باید بیشتر از صفر و بیشتر از کمیسیون باشد.');
        }

        $existing = EscrowHold::query()->where('key', $request->key)->first();

        if ($existing !== null) {
            return $existing->toData();
        }

        $hold = $this->db->transaction(function () use ($request): EscrowHold {
            $hold = EscrowHold::query()->create([
                'uuid' => (string) Str::uuid7(),
                'key' => $request->key,
                'payer_user_id' => $request->payerUserId,
                'payee_user_id' => $request->payeeUserId,
                'amount_toman' => $request->amount->toman,
                'commission_toman' => $request->commission->toman,
                'refunded_toman' => 0,
                'payment_source' => $request->source,
                'status' => EscrowStatus::Held,
                'reference_type' => $request->referenceType,
                'reference_id' => $request->referenceId,
                'held_at' => Carbon::now(),
            ]);

            $this->record($hold, 'held', [
                new LedgerEntryLine($request->source->debitAccount($request->payerUserId), EntryDirection::Debit, $request->amount),
                new LedgerEntryLine(self::escrow(), EntryDirection::Credit, $request->amount),
            ], $request->memo);

            return $hold;
        });

        $this->events->dispatch(new EscrowHoldChanged($hold, $request->payerUserId));

        return $hold->toData();
    }

    public function find(string $uuid): ?EscrowHoldData
    {
        return EscrowHold::query()->where('uuid', $uuid)->first()?->toData();
    }

    public function release(string $uuid, ?int $actorId = null): EscrowHoldData
    {
        return $this->close($uuid, Money::zero(), $actorId, null);
    }

    public function refund(string $uuid, ?int $actorId = null, ?string $reason = null): EscrowHoldData
    {
        return $this->close($uuid, null, $actorId, $reason);
    }

    public function split(string $uuid, Money $toPayer, int $actorId, string $reason): EscrowHoldData
    {
        if (trim($reason) === '') {
            throw new DomainException('تقسیم امانت بدون دلیل ثبت نمی‌شود.');
        }

        return $this->close($uuid, $toPayer, $actorId, $reason);
    }

    /**
     * بستن امانت. `$toPayer` تهی یعنی همه به خریدار.
     */
    private function close(string $uuid, ?Money $toPayer, ?int $actorId, ?string $reason): EscrowHoldData
    {
        $hold = $this->db->transaction(function () use ($uuid, $toPayer, $actorId, $reason): EscrowHold {
            $hold = EscrowHold::query()->where('uuid', $uuid)->lockForUpdate()->firstOrFail();

            if (! $hold->status->isOpen()) {
                throw new DomainException('این امانت پیش‌تر بسته شده است.');
            }

            $amount = $hold->amount();
            $refund = $toPayer ?? $amount;

            if ($refund->isGreaterThan($amount)) {
                throw new DomainException('بازگشتی به خریدار از مبلغ امانت بیشتر است.');
            }

            $released = $amount->minus($refund);
            // کمیسیون فقط به نسبت بخش آزادشده؛ گرد کردن به نفع ارائه‌دهنده.
            $commission = Money::toman(intdiv($hold->commission_toman * $released->toman, $amount->toman));

            $status = match (true) {
                $released->isZero() => EscrowStatus::Refunded,
                $refund->isZero() => EscrowStatus::Released,
                default => EscrowStatus::Split,
            };

            $lines = [new LedgerEntryLine(self::escrow(), EntryDirection::Debit, $amount)];
            $lines = [...$lines, ...array_values(array_filter([
                $refund->isZero() ? null : new LedgerEntryLine(LedgerAccountRef::wallet($hold->payer_user_id), EntryDirection::Credit, $refund),
                $released->minus($commission)->isZero() ? null : new LedgerEntryLine(LedgerAccountRef::vendorPayable($hold->payee_user_id), EntryDirection::Credit, $released->minus($commission)),
                $commission->isZero() ? null : new LedgerEntryLine(new LedgerAccountRef(AccountType::PlatformRevenue), EntryDirection::Credit, $commission),
            ]))];

            $this->record($hold, $status->value, $lines, $reason, $actorId);

            $hold->forceFill([
                'status' => $status,
                'refunded_toman' => $refund->toman,
                'closed_at' => Carbon::now(),
                'closed_by' => $actorId,
                'close_reason' => $reason,
            ])->save();

            return $hold;
        });

        $this->events->dispatch(new EscrowHoldChanged($hold, $actorId));

        return $hold->toData();
    }

    /** @param  list<LedgerEntryLine>  $lines */
    private function record(EscrowHold $hold, string $action, array $lines, ?string $memo, ?int $actorId = null): void
    {
        $this->ledger->record(new LedgerTransactionRequest(
            kind: 'ledger.escrow_'.$action,
            idempotencyKey: 'ledger.escrow_'.$action.':'.$hold->uuid,
            entries: $lines,
            referenceType: $hold->reference_type,
            referenceId: $hold->reference_id,
            memo: $memo,
            createdBy: $actorId,
        ));
    }

    private static function escrow(): LedgerAccountRef
    {
        return new LedgerAccountRef(AccountType::ServiceEscrow);
    }
}
