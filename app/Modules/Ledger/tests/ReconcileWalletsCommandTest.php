<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Tests;

use App\Models\User;
use App\Modules\Ledger\Actions\CreditWalletManually;
use App\Modules\Ledger\Domain\Wallet;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReconcileWalletsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_no_mismatch_when_the_cache_is_correct(): void
    {
        $user = User::factory()->create();
        app(CreditWalletManually::class)->handle($user->id, Money::toman(10_000), null);

        $this->artisan('fbh:reconcile-wallets')
            ->assertExitCode(0)
            ->expectsOutputToContain('همه کیف پول‌ها با دفتر کل تطبیق دارند.');
    }

    public function test_it_reports_and_fixes_a_drifted_cache(): void
    {
        $user = User::factory()->create();
        app(CreditWalletManually::class)->handle($user->id, Money::toman(10_000), null);

        $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();
        $wallet->forceFill(['cached_balance_toman' => 999])->save();

        $this->artisan('fbh:reconcile-wallets')->assertExitCode(1);

        $this->assertSame(999, $wallet->refresh()->cached_balance_toman);

        $this->artisan('fbh:reconcile-wallets', ['--apply' => true])->assertExitCode(0);

        $this->assertSame(10_000, $wallet->refresh()->cached_balance_toman);
    }
}
