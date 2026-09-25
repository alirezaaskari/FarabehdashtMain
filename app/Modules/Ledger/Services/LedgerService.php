<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Services;

use App\Contracts\LedgerBalanceReader;
use App\Contracts\LedgerRecorder;
use App\Modules\Ledger\Domain\LedgerAccount;
use App\Modules\Ledger\Domain\LedgerEntry;
use App\Modules\Ledger\Domain\LedgerTransaction;
use App\Modules\Ledger\Domain\Wallet;
use App\Modules\Ledger\Events\LedgerTransactionRecorded;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Ledger\LedgerReceipt;
use App\Support\Ledger\LedgerTransactionRequest;
use App\Support\Money;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * پیاده‌سازی دفتر کل — قلب بخش ۱۱.
 *
 * دو ناوردا این‌جا اجرا می‌شود، نه در ستون یا Trigger دیتابیس:
 *
 * ۱. **جمع صفر:** جمع بستانکار منهای بدهکار همه ردیف‌های یک تراکنش باید صفر
 *    شود. پیش از هر نوشتنی بررسی می‌شود.
 * ۲. **Idempotency:** اگر تراکنشی با همان `idempotency_key` قبلاً ثبت شده،
 *    نوشتن تازه‌ای انجام نمی‌شود و رسید همان تراکنش قبلی برمی‌گردد.
 *
 *    بررسی پیش از تراکنش دیتابیسی انجام می‌شود تا در مسیر معمول (بدون
 *    هم‌زمانی) از باز کردن تراکنش بی‌مورد پرهیز شود؛ ضامن واقعی idempotency
 *    قید یکتای ستون در دیتابیس است، نه این بررسی. اگر دو درخواست هم‌زمان با
 *    همان کلید به این متد برسند، هر دو از این بررسی رد می‌شوند ولی یکی از
 *    دو `INSERT` با خطای نقض یکتایی شکست می‌خورد — یعنی هرگز دو اثر مالی
 *    برای یک کلید ثبت نمی‌شود؛ فقط فراخوان دوم باید دوباره تلاش کند، که با
 *    بررسی ابتدای متد این‌بار رسید موجود را می‌گیرد. صف‌های کار پروژه همین
 *    الگوی retry را دارند، پس این رفتار برایشان طبیعی است.
 */
final readonly class LedgerService implements LedgerBalanceReader, LedgerRecorder
{
    public function __construct(
        private ConnectionInterface $db,
        private Dispatcher $events,
    ) {}

    public function balanceOf(LedgerAccountRef $ref): Money
    {
        $account = $this->findAccount($ref);

        if ($account === null) {
            return Money::zero();
        }

        $credit = (int) $account->entries()->where('direction', EntryDirection::Credit->value)->sum('amount_toman');
        $debit = (int) $account->entries()->where('direction', EntryDirection::Debit->value)->sum('amount_toman');

        return Money::toman($credit - $debit);
    }

    public function record(LedgerTransactionRequest $request): LedgerReceipt
    {
        $existing = $this->findExisting($request->idempotencyKey);

        if ($existing !== null) {
            return new LedgerReceipt($existing->uuid, alreadyRecorded: true);
        }

        $this->assertBalanced($request);

        return $this->db->transaction(function () use ($request): LedgerReceipt {
            $transaction = LedgerTransaction::query()->create([
                'uuid' => (string) Str::uuid7(),
                'kind' => $request->kind,
                'idempotency_key' => $request->idempotencyKey,
                'reference_type' => $request->referenceType,
                'reference_id' => $request->referenceId,
                'memo' => $request->memo,
                'created_by' => $request->createdBy,
            ]);

            foreach ($request->entries as $line) {
                $account = $this->resolveAccount($line->account);

                LedgerEntry::query()->create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $account->id,
                    'direction' => $line->direction->value,
                    'amount_toman' => $line->amount->toman,
                ]);

                if ($account->type->hasCachedBalance()) {
                    Wallet::query()->where('ledger_account_id', $account->id)->lockForUpdate()->firstOrFail()
                        ->applyLedgerEntry($line->direction, $line->amount);
                }
            }

            $this->events->dispatch(new LedgerTransactionRecorded($transaction));

            return new LedgerReceipt($transaction->uuid, alreadyRecorded: false);
        });
    }

    private function findExisting(string $idempotencyKey): ?LedgerTransaction
    {
        return LedgerTransaction::query()->where('idempotency_key', $idempotencyKey)->first();
    }

    private function assertBalanced(LedgerTransactionRequest $request): void
    {
        if (count($request->entries) < 2) {
            throw new InvalidArgumentException('تراکنش دفتر کل دست‌کم به دو ردیف نیاز دارد.');
        }

        $sum = 0;

        foreach ($request->entries as $line) {
            $sum += $line->amount->toman * $line->direction->sign();
        }

        if ($sum !== 0) {
            throw new InvalidArgumentException('جمع بستانکار و بدهکار یک تراکنش باید صفر شود.');
        }
    }

    private function findAccount(LedgerAccountRef $ref): ?LedgerAccount
    {
        return LedgerAccount::query()
            ->where('type', $ref->type->value)
            ->where('owner_type', $ref->ownerType)
            ->where('owner_id', $ref->ownerId)
            ->first();
    }

    private function resolveAccount(LedgerAccountRef $ref): LedgerAccount
    {
        $account = $this->findAccount($ref);

        if ($account !== null) {
            return $account;
        }

        $account = LedgerAccount::query()->create([
            'uuid' => (string) Str::uuid7(),
            'type' => $ref->type->value,
            'owner_type' => $ref->ownerType,
            'owner_id' => $ref->ownerId,
            'currency' => 'toman',
        ]);

        if ($ref->type->hasCachedBalance()) {
            $this->createWalletFor($ref, $account);
        }

        return $account;
    }

    private function createWalletFor(LedgerAccountRef $ref, LedgerAccount $account): void
    {
        if ($ref->type !== AccountType::UserWallet || $ref->ownerId === null) {
            throw new InvalidArgumentException('حساب با موجودی کش‌شده بدون شناسه مالک ساخته نمی‌شود.');
        }

        Wallet::query()->create([
            'uuid' => (string) Str::uuid7(),
            'user_id' => $ref->ownerId,
            'ledger_account_id' => $account->id,
        ]);
    }
}
