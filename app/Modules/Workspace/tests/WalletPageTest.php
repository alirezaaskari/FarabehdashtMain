<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Tests;

use App\Models\User;
use App\Modules\Ledger\Actions\CreditWalletManually;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * کیف پول کاربر — فقط خواندنی.
 */
final class WalletPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_without_transactions_sees_a_zero_balance_and_an_empty_state(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('workspace.wallet'))
            ->assertOk()
            ->assertSee('۰ تومان')
            ->assertSee('هنوز تراکنشی ندارید');
    }

    public function test_the_statement_lists_movements_without_the_admins_private_memo(): void
    {
        $user = User::factory()->create();
        $this->app->make(CreditWalletManually::class)->handle($user->id, Money::toman(300_000), null, 'پیگیری بانک ۹۸۷۶ — خانم الف');

        $this->actingAs($user)
            ->get(route('workspace.wallet'))
            ->assertOk()
            ->assertSee('۳۰۰٬۰۰۰ تومان')
            ->assertSee('شارژ کیف پول توسط پشتیبانی')
            ->assertDontSee('پیگیری بانک');
    }

    public function test_the_page_offers_no_way_to_charge_the_wallet(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('workspace.wallet'))
            ->assertOk()
            ->assertDontSee('name="amount"', escape: false)
            ->assertSee('فقط از راه پشتیبانی شارژ می‌شود');
    }

    public function test_the_statement_is_paginated_and_newest_first(): void
    {
        config(['workspace.wallet.per_page' => 2]);
        $user = User::factory()->create();

        foreach ([1_000, 2_000, 3_000] as $amount) {
            $this->app->make(CreditWalletManually::class)->handle($user->id, Money::toman($amount), null);
        }

        $this->actingAs($user)
            ->get(route('workspace.wallet'))
            ->assertSeeInOrder(['+۳٬۰۰۰ تومان', '+۲٬۰۰۰ تومان'])
            ->assertDontSee('+۱٬۰۰۰ تومان')
            ->assertSee('صفحه ۱ از ۲');

        $this->get(route('workspace.wallet', ['page' => 2]))->assertSee('+۱٬۰۰۰ تومان');
    }
}
