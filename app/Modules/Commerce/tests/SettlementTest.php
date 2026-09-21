<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Tests;

use App\Contracts\LedgerBalanceReader;
use App\Contracts\LedgerRecorder;
use App\Models\User;
use App\Modules\Commerce\Actions\SettleVendor;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Ledger\LedgerEntryLine;
use App\Support\Ledger\LedgerTransactionRequest;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

final class SettlementTest extends TestCase
{
    use RefreshDatabase;

    private function creditVendor(int $vendorUserId, int $amountToman): void
    {
        app(LedgerRecorder::class)->record(new LedgerTransactionRequest(
            kind: 'test.vendor_credit',
            idempotencyKey: (string) Str::uuid7(),
            entries: [
                new LedgerEntryLine(new LedgerAccountRef(AccountType::Treasury), EntryDirection::Debit, Money::toman($amountToman)),
                new LedgerEntryLine(LedgerAccountRef::vendorPayable($vendorUserId), EntryDirection::Credit, Money::toman($amountToman)),
            ],
        ));
    }

    public function test_settling_reduces_the_vendors_payable_balance(): void
    {
        $admin = User::factory()->create();
        $vendor = User::factory()->create();
        $this->creditVendor($vendor->id, 200_000);

        $this->app->make(SettleVendor::class)->handle($vendor->id, Money::toman(150_000), $admin->id, 'واریز بانکی هفتگی');

        $owed = app(LedgerBalanceReader::class)->balanceOf(LedgerAccountRef::vendorPayable($vendor->id));
        $this->assertSame(50_000, $owed->toman);
    }

    public function test_settling_more_than_owed_is_rejected(): void
    {
        $admin = User::factory()->create();
        $vendor = User::factory()->create();
        $this->creditVendor($vendor->id, 10_000);

        $this->expectException(InvalidArgumentException::class);

        $this->app->make(SettleVendor::class)->handle($vendor->id, Money::toman(20_000), $admin->id);
    }

    public function test_settling_zero_is_rejected(): void
    {
        $admin = User::factory()->create();
        $vendor = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        $this->app->make(SettleVendor::class)->handle($vendor->id, Money::zero(), $admin->id);
    }

    public function test_replaying_the_same_settlement_does_not_double_pay(): void
    {
        // این اکشن برخلاف پرداخت سفارش، کلید idempotency تصادفی می‌سازد
        // (هر تسویه یک تصمیم دستی مستقل مدیر است)، پس این تست خودِ ناوردای
        // «مبلغ تسویه هرگز از بدهی واقعی بیشتر نمی‌شود» را با دو تسویه پیاپی
        // می‌سنجد، نه بازپخش یک کلید.
        $admin = User::factory()->create();
        $vendor = User::factory()->create();
        $this->creditVendor($vendor->id, 100_000);

        $this->app->make(SettleVendor::class)->handle($vendor->id, Money::toman(60_000), $admin->id);

        $this->expectException(InvalidArgumentException::class);
        $this->app->make(SettleVendor::class)->handle($vendor->id, Money::toman(60_000), $admin->id);
    }
}
