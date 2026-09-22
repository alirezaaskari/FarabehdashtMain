<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Tests;

use App\Models\User;
use App\Modules\Ledger\Actions\CreditWalletManually;
use App\Modules\Ledger\Domain\LedgerTransaction;
use App\Modules\Ledger\Domain\Wallet;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreditWalletManuallyTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_wallet_and_credits_it_for_a_first_time_user(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();

        $this->assertSame(0, Wallet::query()->where('user_id', $user->id)->count());

        app(CreditWalletManually::class)->handle($user->id, Money::toman(75_000), $admin->id, 'واریز نقدی باجه');

        $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(75_000, $wallet->cached_balance_toman);

        $transaction = LedgerTransaction::query()->where('kind', 'wallet.manual_topup')->firstOrFail();
        $this->assertSame($admin->id, $transaction->created_by);
        $this->assertSame('واریز نقدی باجه', $transaction->memo);
    }

    public function test_a_second_charge_adds_to_the_existing_balance(): void
    {
        $user = User::factory()->create();

        app(CreditWalletManually::class)->handle($user->id, Money::toman(10_000), null);
        app(CreditWalletManually::class)->handle($user->id, Money::toman(5_000), null);

        $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(15_000, $wallet->cached_balance_toman);
    }
}
