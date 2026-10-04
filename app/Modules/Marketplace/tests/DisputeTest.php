<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Tests;

use App\Contracts\LedgerBalanceReader;
use App\Models\User;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Ledger\Actions\CreditWalletManually;
use App\Modules\Ledger\Domain\Wallet;
use App\Modules\Marketplace\Domain\Enums\ContractStatus;
use App\Modules\Marketplace\Domain\Enums\MilestoneStatus;
use App\Modules\Marketplace\Domain\Enums\ProjectStatus;
use App\Modules\Marketplace\Domain\MarketBid;
use App\Modules\Marketplace\Domain\MarketContract;
use App\Modules\Marketplace\Domain\MarketMilestone;
use App\Modules\Marketplace\Domain\MarketProject;
use App\Modules\Marketplace\Filament\Pages\MarketDisputesPage;
use App\Modules\Workspace\Domain\UserNotification;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * اعتراض، رأی مدیر و لغو (بخش ۲۱-۵): بازگشت همیشه به کیف پول (DEC-82)،
 * کمیسیون فقط از بخش آزادشده، و لغو پس از گذشتن مهلت تحویل (DEC-83).
 */
final class DisputeTest extends TestCase
{
    use MarketFixtures;
    use RefreshDatabase;

    private const REASON = 'گزارش نهایی نقشه صدای سالن دوم را ندارد و داده خام هم پیوست نشده است.';

    private User $client;

    private User $provider;

    private MarketProject $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = $this->provider();
        $this->client = $this->client();
        $this->project = $this->publishedProject($this->client);
    }

    public function test_a_dispute_stops_auto_release_and_a_split_takes_commission_only_from_the_released_part(): void
    {
        $milestone = $this->funded();
        $this->actingAs($this->provider)->post(route('market.milestones.deliver', $milestone->uuid), ['note' => 'گزارش تحویل شد.'])->assertSessionHasNoErrors();
        $this->actingAs($this->client)->post(route('market.milestones.dispute', $milestone->uuid), ['reason' => self::REASON])->assertSessionHasNoErrors();

        $this->assertSame(MilestoneStatus::Disputed, $milestone->refresh()->status);
        $this->assertSame(1, UserNotification::query()->where('user_id', $this->provider->id)->where('kind', 'marketplace.milestone_disputed')->count());

        Carbon::setTestNow(Carbon::now()->addDays(10));
        $this->artisan('marketplace:sweep')->assertSuccessful();
        Carbon::setTestNow();
        $this->assertSame(MilestoneStatus::Disputed, $milestone->refresh()->status);

        $this->actingAs($this->admin(AdminRole::Content));
        $this->get(route('filament.fbh.pages.market-disputes'))->assertForbidden();

        $this->actingAs($this->admin(AdminRole::Finance));
        Livewire::test(MarketDisputesPage::class)
            ->assertSee('سالن دوم')
            ->set('amounts.m'.$milestone->id, 2_000_000)
            ->set('notes.m'.$milestone->id, 'یک سالن از دو سالن تحویل نشد.')
            ->call('split', $milestone->id)
            ->assertSet('rows', []);

        $milestone->refresh();
        $this->assertSame(MilestoneStatus::Settled, $milestone->status);
        $this->assertSame(2_000_000, $milestone->refunded_toman);
        $this->assertSame(0, $this->balance(AccountType::ProjectEscrow));
        $this->assertSame(400_000, $this->balance(AccountType::PlatformRevenue));
        $this->assertSame(ContractStatus::Cancelled, $milestone->contract->status);
        $this->assertSame(ProjectStatus::Closed, $this->project->refresh()->status);
        $this->actingAs($this->client)->get(route('market.contracts.show', $milestone->contract->uuid))->assertSee('یک سالن از دو سالن تحویل نشد.');
    }

    public function test_releasing_in_full_keeps_the_contract_going(): void
    {
        $milestone = $this->funded();
        $this->actingAs($this->provider)->post(route('market.milestones.dispute', $milestone->uuid), ['reason' => 'کارفرما دسترسی به سالن را برای اندازه‌گیری نمی‌دهد و کار عقب افتاده.'])->assertSessionHasNoErrors();

        $this->actingAs($this->admin(AdminRole::Finance));
        Livewire::test(MarketDisputesPage::class)->set('notes.m'.$milestone->id, 'کار انجام شده است.')->call('releaseAll', $milestone->id);

        $this->assertSame(MilestoneStatus::Released, $milestone->refresh()->status);
        $this->assertSame(ContractStatus::Active, $milestone->contract->status);
        $this->assertSame(600_000, $this->balance(AccountType::PlatformRevenue));
    }

    public function test_cancelling_before_payment_is_free_and_after_payment_needs_the_provider(): void
    {
        $milestone = $this->funded();

        $this->actingAs($this->client)->post(route('market.contracts.cancel', $milestone->contract->uuid))->assertSessionHasErrors('contract');
        $this->actingAs($this->client)->post(route('market.milestones.cancel.request', $milestone->uuid))->assertSessionHasNoErrors();
        $this->assertSame(1, UserNotification::query()->where('user_id', $this->provider->id)->where('kind', 'marketplace.cancel_requested')->count());

        $this->actingAs($this->provider)->post(route('market.milestones.cancel.respond', $milestone->uuid), ['agree' => '0'])->assertSessionHasNoErrors();
        $this->assertNull($milestone->refresh()->cancel_requested_at);
        $this->assertSame(MilestoneStatus::Funded, $milestone->status);

        $this->actingAs($this->client)->post(route('market.milestones.cancel.request', $milestone->uuid))->assertSessionHasNoErrors();
        $before = $this->wallet($this->client);
        $this->actingAs($this->provider)->post(route('market.milestones.cancel.respond', $milestone->uuid), ['agree' => '1'])->assertSessionHasNoErrors();

        $this->assertSame(MilestoneStatus::Refunded, $milestone->refresh()->status);
        $this->assertSame($before + 6_000_000, $this->wallet($this->client));
        $this->assertSame(ContractStatus::Cancelled, $milestone->contract->status);
        $this->assertSame(0, $this->balance(AccountType::PlatformRevenue));
    }

    public function test_an_unpaid_contract_is_cancelled_for_free(): void
    {
        $contract = $this->accepted();

        $this->actingAs($this->provider)->post(route('market.contracts.cancel', $contract->uuid))->assertSessionHasErrors('contract');
        $this->assertSame(ContractStatus::AwaitingPayment, $contract->refresh()->status);
        $this->actingAs($this->client)->post(route('market.contracts.cancel', $contract->uuid))->assertSessionHasNoErrors();
        $this->assertSame(ContractStatus::Cancelled, $contract->refresh()->status);
    }

    public function test_the_client_cancels_alone_three_days_after_a_missed_deadline(): void
    {
        $milestone = $this->funded();

        $this->actingAs($this->client)->post(route('market.milestones.cancel.overdue', $milestone->uuid))->assertSessionHasErrors('contract');

        Carbon::setTestNow(Carbon::now()->addDays($milestone->days + 4));
        $this->actingAs($this->client)->get(route('market.contracts.show', $milestone->contract->uuid))->assertSee('لغو و بازگشت پول مرحله');
        $this->actingAs($this->client)->post(route('market.milestones.cancel.overdue', $milestone->uuid))->assertSessionHasNoErrors();
        Carbon::setTestNow();

        $this->assertSame(MilestoneStatus::Refunded, $milestone->refresh()->status);
        $this->assertSame(6_000_000, $milestone->refunded_toman);
        $this->assertSame(ContractStatus::Cancelled, $milestone->contract->status);
    }

    public function test_contact_details_are_refused_in_a_dispute(): void
    {
        $milestone = $this->funded();

        $this->actingAs($this->client)->post(route('market.milestones.dispute', $milestone->uuid), ['reason' => 'مجری جواب نمی‌دهد، لطفاً با ۰۹۱۲۳۴۵۶۷۸۹ تماس بگیرید تا هماهنگ کنیم.'])->assertSessionHasErrors('contract');
        $this->assertSame(MilestoneStatus::Funded, $milestone->refresh()->status);
    }

    private function accepted(): MarketContract
    {
        $this->actingAs($this->provider)->post(route('market.bid.store', $this->project->id), $this->bidForm())->assertSessionHasNoErrors();
        $bid = MarketBid::query()->sole();
        $this->actingAs($this->client)->post(route('market.contracts.accept', $bid->uuid))->assertSessionHasNoErrors();

        return MarketContract::query()->sole();
    }

    private function funded(): MarketMilestone
    {
        $contract = $this->accepted();
        app(CreditWalletManually::class)->handle($this->client->id, Money::toman(20_000_000), null, idempotencyKey: (string) Str::uuid7());
        $this->actingAs($this->client)->post(route('market.milestones.pay', $contract->milestones[0]->uuid), ['payment' => 'wallet'])->assertSessionHasNoErrors();

        return $contract->milestones[0]->refresh();
    }

    private function wallet(User $user): int
    {
        return Wallet::query()->where('user_id', $user->id)->value('cached_balance_toman') ?? 0;
    }

    private function balance(AccountType $type): int
    {
        return $this->app->make(LedgerBalanceReader::class)->balanceOf(new LedgerAccountRef($type))->toman;
    }
}
