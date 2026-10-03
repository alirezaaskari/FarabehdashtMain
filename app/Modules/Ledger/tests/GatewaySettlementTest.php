<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Tests;

use App\Contracts\LedgerRecorder;
use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Ledger\Actions\SettleGatewayClearing;
use App\Modules\Ledger\Services\LedgerService;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Ledger\LedgerEntryLine;
use App\Support\Ledger\LedgerTransactionRequest;
use App\Support\Money;
use App\Support\Payments\PaymentSource;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * حساب واسط درگاه (بخش ۱۹-۱): پرداخت درگاه تا واریز بانکی در حساب واسط می‌ماند.
 */
final class GatewaySettlementTest extends TestCase
{
    use RefreshDatabase;

    private function gatewayPayment(int $toman): void
    {
        $buyer = User::factory()->create();

        app(LedgerRecorder::class)->record(new LedgerTransactionRequest(
            kind: 'commerce.order_paid',
            idempotencyKey: 'test:'.$buyer->id,
            entries: [
                new LedgerEntryLine(PaymentSource::Gateway->debitAccount($buyer->id), EntryDirection::Debit, Money::toman($toman)),
                new LedgerEntryLine(new LedgerAccountRef(AccountType::PlatformRevenue), EntryDirection::Credit, Money::toman($toman)),
            ],
        ));
    }

    private function finance(): User
    {
        $user = User::factory()->create();
        app(GrantAdminRole::class)->handle($user, AdminRole::Finance);

        return $user->fresh() ?? $user;
    }

    public function test_a_gateway_payment_waits_in_clearing_until_the_bank_deposit_is_recorded(): void
    {
        $this->gatewayPayment(300_000);
        $this->gatewayPayment(200_000);

        $action = app(SettleGatewayClearing::class);
        $this->assertSame(500_000, $action->outstanding()->toman);

        $action->handle(Money::toman(300_000), 'ZP-81234567', $this->finance()->id);
        $again = $action->handle(Money::toman(300_000), 'zp-81234567', $this->finance()->id);

        $this->assertTrue($again->alreadyRecorded);
        $this->assertSame(200_000, $action->outstanding()->toman);
        $this->assertSame(300_000, app(LedgerService::class)->debitBalanceOf(new LedgerAccountRef(AccountType::Treasury))->toman);
        $this->assertDatabaseHas('audit_logs', ['action' => 'ledger.gateway_settled']);
    }

    public function test_a_deposit_larger_than_what_the_gateway_owes_is_refused(): void
    {
        $this->gatewayPayment(100_000);

        $this->expectException(DomainException::class);

        app(SettleGatewayClearing::class)->handle(Money::toman(100_001), 'ZP-1', $this->finance()->id);
    }

    public function test_only_finance_opens_the_page_and_it_has_a_guide(): void
    {
        $this->actingAs($this->finance())
            ->get('/'.config('admin.path').'/gateway-settlement')
            ->assertOk()
            ->assertSee('پول واریزنشده درگاه')
            ->assertSee('مانده امانت وجه خدمت')
            ->assertSee('مانده امانت وجه پروژه')
            ->assertSee('data-page-help="filament.fbh.pages.gateway-settlement"', false);

        $content = User::factory()->create();
        app(GrantAdminRole::class)->handle($content, AdminRole::Content);

        $this->actingAs($content->fresh() ?? $content)
            ->get('/'.config('admin.path').'/gateway-settlement')
            ->assertForbidden();
    }
}
