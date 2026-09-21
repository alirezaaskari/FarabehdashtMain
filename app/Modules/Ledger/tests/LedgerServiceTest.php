<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Tests;

use App\Contracts\LedgerRecorder;
use App\Models\User;
use App\Modules\Ledger\Domain\LedgerEntry;
use App\Modules\Ledger\Domain\LedgerTransaction;
use App\Modules\Ledger\Domain\Wallet;
use App\Modules\Ledger\Events\LedgerTransactionRecorded;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Ledger\LedgerEntryLine;
use App\Support\Ledger\LedgerReceipt;
use App\Support\Ledger\LedgerTransactionRequest;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * معیار پذیرش بخش ۱۱: «مجموع هر تراکنش صفر است؛ اجرای دوباره با همان کلید
 * اثر دوم ندارد.»
 */
final class LedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    private function topUp(User $user, int $toman, ?string $key = null): LedgerReceipt
    {
        return app(LedgerRecorder::class)->record(new LedgerTransactionRequest(
            kind: 'wallet.manual_topup',
            idempotencyKey: $key ?? (string) Str::uuid7(),
            entries: [
                new LedgerEntryLine(new LedgerAccountRef(AccountType::Treasury), EntryDirection::Debit, Money::toman($toman)),
                new LedgerEntryLine(LedgerAccountRef::wallet($user->id), EntryDirection::Credit, Money::toman($toman)),
            ],
        ));
    }

    public function test_it_records_a_balanced_transaction_and_updates_the_wallet_cache(): void
    {
        $user = User::factory()->create();

        $receipt = $this->topUp($user, 50_000);

        $this->assertFalse($receipt->alreadyRecorded);
        $this->assertSame(1, LedgerTransaction::query()->count());
        $this->assertSame(2, LedgerEntry::query()->count());

        $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(50_000, $wallet->cached_balance_toman);
    }

    public function test_replaying_the_same_idempotency_key_has_no_second_effect(): void
    {
        $user = User::factory()->create();
        $key = (string) Str::uuid7();

        $first = $this->topUp($user, 20_000, $key);
        $second = $this->topUp($user, 20_000, $key);

        $this->assertFalse($first->alreadyRecorded);
        $this->assertTrue($second->alreadyRecorded);
        $this->assertSame($first->transactionUuid, $second->transactionUuid);

        $this->assertSame(1, LedgerTransaction::query()->count());
        $this->assertSame(2, LedgerEntry::query()->count());

        $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(20_000, $wallet->cached_balance_toman);
    }

    public function test_it_dispatches_an_auditable_event_only_on_a_genuine_write(): void
    {
        Event::fake([LedgerTransactionRecorded::class]);

        $user = User::factory()->create();
        $key = (string) Str::uuid7();

        $this->topUp($user, 15_000, $key);
        $this->topUp($user, 15_000, $key);

        Event::assertDispatchedTimes(LedgerTransactionRecorded::class, 1);
    }

    public function test_it_rejects_an_unbalanced_transaction(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        app(LedgerRecorder::class)->record(new LedgerTransactionRequest(
            kind: 'wallet.manual_topup',
            idempotencyKey: (string) Str::uuid7(),
            entries: [
                new LedgerEntryLine(new LedgerAccountRef(AccountType::Treasury), EntryDirection::Debit, Money::toman(10_000)),
                new LedgerEntryLine(LedgerAccountRef::wallet($user->id), EntryDirection::Credit, Money::toman(9_000)),
            ],
        ));
    }

    public function test_it_rejects_a_transaction_with_fewer_than_two_lines(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(LedgerRecorder::class)->record(new LedgerTransactionRequest(
            kind: 'wallet.manual_topup',
            idempotencyKey: (string) Str::uuid7(),
            entries: [
                new LedgerEntryLine(new LedgerAccountRef(AccountType::Treasury), EntryDirection::Debit, Money::toman(1_000)),
            ],
        ));
    }

    public function test_debiting_a_wallet_below_zero_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->topUp($user, 10_000);

        $this->expectException(InvalidArgumentException::class);

        // برداشت ۲۰٬۰۰۰ از کیف پولی که فقط ۱۰٬۰۰۰ دارد — Money خودش رد می‌کند.
        app(LedgerRecorder::class)->record(new LedgerTransactionRequest(
            kind: 'wallet.spend',
            idempotencyKey: (string) Str::uuid7(),
            entries: [
                new LedgerEntryLine(LedgerAccountRef::wallet($user->id), EntryDirection::Debit, Money::toman(20_000)),
                new LedgerEntryLine(new LedgerAccountRef(AccountType::PlatformRevenue), EntryDirection::Credit, Money::toman(20_000)),
            ],
        ));
    }

    public function test_the_sum_of_every_recorded_transaction_is_zero(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->topUp($first, 30_000);
        $this->topUp($second, 12_500);

        $sumsByTransaction = LedgerEntry::query()
            ->selectRaw('transaction_id, direction, SUM(amount_toman) as total')
            ->groupBy('transaction_id', 'direction')
            ->get()
            ->groupBy('transaction_id');

        $this->assertGreaterThan(0, $sumsByTransaction->count());

        foreach ($sumsByTransaction as $rows) {
            // خودِ LedgerEntry حتی روی ستون‌های selectRaw هم cast مربوط به
            // «direction» را اعمال می‌کند، پس این‌جا از قبل EntryDirection است.
            $net = $rows->sum(
                fn (object $row): int => (int) $row->total * $row->direction->sign(),
            );

            $this->assertSame(0, $net);
        }
    }
}
