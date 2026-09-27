<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Tests;

use App\Contracts\EscrowKeeper;
use App\Contracts\LedgerBalanceReader;
use App\Models\User;
use App\Modules\Ledger\Actions\CreditWalletManually;
use App\Modules\Ledger\Domain\Wallet;
use App\Modules\Ledger\Services\LedgerService;
use App\Support\Escrow\EscrowHold;
use App\Support\Escrow\EscrowHoldRequest;
use App\Support\Escrow\EscrowStatus;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Money;
use App\Support\Payments\PaymentSource;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Throwable;

/**
 * نگه‌داری پول خدمت تا پایان کار (بخش ۱۹-۱).
 */
final class EscrowTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;

    private User $consultant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buyer = User::factory()->create();
        $this->consultant = User::factory()->create();
    }

    private function hold(PaymentSource $source = PaymentSource::Gateway, string $key = 'consulting:1'): EscrowHold
    {
        return app(EscrowKeeper::class)->hold(new EscrowHoldRequest(
            key: $key,
            payerUserId: $this->buyer->id,
            payeeUserId: $this->consultant->id,
            amount: Money::toman(1_000_000),
            commission: Money::toman(150_000),
            source: $source,
            referenceType: 'consulting_request',
            referenceId: $key,
        ));
    }

    private function balance(LedgerAccountRef $ref): int
    {
        return app(LedgerBalanceReader::class)->balanceOf($ref)->toman;
    }

    private function wallet(User $user): int
    {
        return Wallet::query()->where('user_id', $user->id)->value('cached_balance_toman') ?? 0;
    }

    public function test_gateway_money_waits_in_escrow_and_is_released_minus_commission(): void
    {
        $hold = $this->hold();

        $this->assertSame(EscrowStatus::Held, $hold->status);
        $this->assertSame(1_000_000, $this->balance(new LedgerAccountRef(AccountType::ServiceEscrow)));
        $this->assertSame(1_000_000, app(LedgerService::class)->debitBalanceOf(new LedgerAccountRef(AccountType::GatewayClearing))->toman);
        $this->assertSame(0, $this->balance(LedgerAccountRef::vendorPayable($this->consultant->id)));

        $released = app(EscrowKeeper::class)->release($hold->uuid);

        $this->assertSame(EscrowStatus::Released, $released->status);
        $this->assertSame(0, $this->balance(new LedgerAccountRef(AccountType::ServiceEscrow)));
        $this->assertSame(850_000, $this->balance(LedgerAccountRef::vendorPayable($this->consultant->id)));
        $this->assertSame(150_000, $this->balance(new LedgerAccountRef(AccountType::PlatformRevenue)));
    }

    public function test_the_same_key_holds_once(): void
    {
        $first = $this->hold();
        $second = $this->hold();

        $this->assertSame($first->uuid, $second->uuid);
        $this->assertSame(1_000_000, $this->balance(new LedgerAccountRef(AccountType::ServiceEscrow)));
    }

    public function test_a_refund_goes_whole_to_the_buyer_wallet(): void
    {
        app(CreditWalletManually::class)->handle($this->buyer->id, Money::toman(1_200_000), null);
        $hold = $this->hold(PaymentSource::Wallet);
        $this->assertSame(200_000, $this->wallet($this->buyer));

        $refunded = app(EscrowKeeper::class)->refund($hold->uuid, null, 'مشاور پاسخ نداد');

        $this->assertSame(EscrowStatus::Refunded, $refunded->status);
        $this->assertSame(1_200_000, $this->wallet($this->buyer));
        $this->assertSame(0, $this->balance(new LedgerAccountRef(AccountType::PlatformRevenue)));
    }

    public function test_a_split_takes_commission_only_on_the_released_part(): void
    {
        $hold = $this->hold();

        $split = app(EscrowKeeper::class)->split($hold->uuid, Money::toman(400_000), $this->consultant->id, 'نیمی از کار انجام شد');

        $this->assertSame(EscrowStatus::Split, $split->status);
        $this->assertSame(400_000, $this->wallet($this->buyer));
        $this->assertSame(90_000, $this->balance(new LedgerAccountRef(AccountType::PlatformRevenue)));
        $this->assertSame(510_000, $this->balance(LedgerAccountRef::vendorPayable($this->consultant->id)));
        $this->assertSame(0, $this->balance(new LedgerAccountRef(AccountType::ServiceEscrow)));
    }

    public function test_a_closed_hold_cannot_close_again(): void
    {
        $hold = $this->hold();
        app(EscrowKeeper::class)->release($hold->uuid);

        $this->expectException(DomainException::class);

        app(EscrowKeeper::class)->refund($hold->uuid);
    }

    public function test_a_wallet_hold_without_enough_balance_leaves_nothing_behind(): void
    {
        try {
            $this->hold(PaymentSource::Wallet);
            $this->fail('hold should have failed');
        } catch (Throwable) {
            // موجودی کافی نیست.
        }

        $this->assertNull(app(EscrowKeeper::class)->find('x'));
        $this->assertDatabaseCount('escrow_holds', 0);
    }

    public function test_every_move_is_audited(): void
    {
        $hold = $this->hold();
        app(EscrowKeeper::class)->refund($hold->uuid, null, 'لغو');

        $this->assertDatabaseHas('audit_logs', ['action' => 'ledger.escrow_held', 'subject_id' => $hold->uuid]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'ledger.escrow_refunded', 'subject_id' => $hold->uuid]);
    }
}
