<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Tests;

use App\Contracts\LedgerBalanceReader;
use App\Contracts\PaymentGateway;
use App\Models\User;
use App\Modules\Ledger\Actions\CreditWalletManually;
use App\Modules\Marketplace\Domain\Enums\BidStatus;
use App\Modules\Marketplace\Domain\Enums\ContractStatus;
use App\Modules\Marketplace\Domain\Enums\MilestoneStatus;
use App\Modules\Marketplace\Domain\Enums\ProjectStatus;
use App\Modules\Marketplace\Domain\MarketBid;
use App\Modules\Marketplace\Domain\MarketContract;
use App\Modules\Marketplace\Domain\MarketMilestone;
use App\Modules\Marketplace\Domain\MarketProject;
use App\Modules\Marketplace\Services\ProjectAccess;
use App\Modules\Monetization\Actions\ToggleRevenueStream;
use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Workspace\Domain\UserNotification;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Money;
use App\Support\Payments\FakeZarinPalGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * قرارداد و پرداخت مرحله‌ای (بخش ۲۱-۴): پذیرش و انجماد مرحله‌ها، امانت
 * جدای هر مرحله، کمیسیون از سهم مجری (DEC-75)، اصلاح، آزادسازی خودکار و
 * بی‌اثر شدن قرارداد پرداخت‌نشده (DEC-81).
 */
final class ContractTest extends TestCase
{
    use MarketFixtures;
    use RefreshDatabase;

    private User $client;

    private User $provider;

    private MarketProject $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(PaymentGateway::class, new FakeZarinPalGateway);
        Storage::fake('local');

        $this->provider = $this->provider();
        $this->client = $this->client();
        $this->project = $this->publishedProject($this->client);
    }

    public function test_the_full_cycle_holds_each_milestone_and_releases_ninety_percent(): void
    {
        $rival = $this->provider('ali-rad', 'علی راد');
        $this->bidAs($rival);
        $bid = $this->bidAs($this->provider);

        $this->actingAs($this->client)->get(route('market.bids.show', $bid->uuid))->assertSee('پذیرش این پیشنهاد');
        $this->actingAs($this->client)->post(route('market.contracts.accept', $bid->uuid))->assertSessionHasNoErrors();

        $contract = MarketContract::query()->sole();
        $this->assertSame(ContractStatus::AwaitingPayment, $contract->status);
        $this->assertSame(1000, $contract->commission_bp);
        $this->assertCount(2, $contract->milestones);
        $this->assertSame(ProjectStatus::Awarded, $this->project->refresh()->status);
        $this->assertSame(BidStatus::Declined, MarketBid::query()->where('provider_user_id', $rival->id)->sole()->status);
        $this->assertSame(1, UserNotification::query()->where('user_id', $rival->id)->where('kind', 'marketplace.bid_declined')->count());

        // پیوست خصوصی پیش از پرداخت برای مجری بسته است.
        $first = $contract->milestones[0];
        $this->actingAs($this->provider)->post(route('market.milestones.deliver', $first->uuid), ['note' => 'تحویل پیش از پرداخت'])->assertSessionHasErrors('contract');

        app(CreditWalletManually::class)->handle($this->client->id, Money::toman(20_000_000), null, idempotencyKey: (string) Str::uuid7());
        $this->actingAs($this->client)->get(route('market.contracts.show', $contract->uuid))->assertOk()->assertSee('data-page-help="market-contract"', false);
        $this->actingAs($this->client)->post(route('market.milestones.pay', $contract->milestones[1]->uuid), ['payment' => 'wallet'])->assertSessionHasErrors('contract');
        $this->actingAs($this->client)->post(route('market.milestones.pay', $first->uuid), ['payment' => 'wallet'])->assertSessionHasNoErrors();

        $first->refresh();
        $this->assertSame(MilestoneStatus::Funded, $first->status);
        $this->assertSame(600_000, $first->commission_toman);
        $this->assertSame(ContractStatus::Active, $contract->refresh()->status);
        $this->assertSame(6_000_000, $this->balance(AccountType::ProjectEscrow));
        $this->assertSame(1, UserNotification::query()->where('user_id', $this->provider->id)->where('kind', 'marketplace.milestone_funded')->count());

        $this->actingAs($this->provider)->post(route('market.milestones.deliver', $first->uuid), [
            'note' => 'اندازه‌گیری دو شیفت انجام شد؛ داده خام پیوست است.',
            'files' => [UploadedFile::fake()->create('noise.pdf', 120, 'application/pdf')],
        ])->assertSessionHasNoErrors();
        $this->assertSame(MilestoneStatus::Delivered, $first->refresh()->status);

        $this->actingAs($this->client)->post(route('market.milestones.revise', $first->uuid), ['revision_note' => 'نقشه سالن دوم کامل نیست.'])->assertSessionHasNoErrors();
        $this->assertSame(MilestoneStatus::Revising, $first->refresh()->status);
        $this->actingAs($this->provider)->post(route('market.milestones.deliver', $first->uuid), ['note' => 'نقشه سالن دوم کامل شد.'])->assertSessionHasNoErrors();

        $this->actingAs($this->client)->post(route('market.milestones.approve', $first->uuid))->assertSessionHasNoErrors();
        $this->assertSame(MilestoneStatus::Released, $first->refresh()->status);
        $this->assertSame(0, $this->balance(AccountType::ProjectEscrow));
        $this->assertSame(600_000, $this->balance(AccountType::PlatformRevenue));

        $second = $contract->milestones()->where('position', 2)->sole();
        $this->actingAs($this->client)->post(route('market.milestones.pay', $second->uuid), ['payment' => 'wallet'])->assertSessionHasNoErrors();
        $this->actingAs($this->provider)->post(route('market.milestones.deliver', $second->uuid), ['note' => 'گزارش نهایی تحویل شد.'])->assertSessionHasNoErrors();

        // کارفرما پاسخ نمی‌دهد؛ پس از مهلت پول خودکار آزاد می‌شود و قرارداد تمام.
        Carbon::setTestNow(Carbon::now()->addDays(8));
        $this->artisan('marketplace:sweep')->assertSuccessful();
        Carbon::setTestNow();

        $this->assertTrue($second->refresh()->auto_released);
        $this->assertSame(ContractStatus::Completed, $contract->refresh()->status);
        $this->assertSame(ProjectStatus::Completed, $this->project->refresh()->status);
        $this->assertSame(1_000_000, $this->balance(AccountType::PlatformRevenue));
        $this->assertSame(1, UserNotification::query()->where('user_id', $this->client->id)->where('kind', 'marketplace.milestone_auto_released')->count());
    }

    public function test_revisions_are_capped(): void
    {
        config(['marketplace.contracts.revisions_max' => 1]);
        $first = $this->fundedFirstMilestone();

        $this->actingAs($this->provider)->post(route('market.milestones.deliver', $first->uuid), ['note' => 'تحویل اول مرحله'])->assertSessionHasNoErrors();
        $this->actingAs($this->client)->post(route('market.milestones.revise', $first->uuid), ['revision_note' => 'لطفاً شیفت شب را هم بسنجید.'])->assertSessionHasNoErrors();
        $this->actingAs($this->provider)->post(route('market.milestones.deliver', $first->uuid), ['note' => 'شیفت شب هم سنجیده شد.'])->assertSessionHasNoErrors();
        $this->actingAs($this->client)->post(route('market.milestones.revise', $first->uuid), ['revision_note' => 'باز هم اصلاح لازم است.'])->assertSessionHasErrors('contract');
    }

    public function test_the_gateway_callback_funds_the_milestone_once(): void
    {
        $contract = $this->accepted();
        $first = $contract->milestones[0];

        $this->actingAs($this->client)->post(route('market.milestones.pay', $first->uuid), ['payment' => 'gateway'])->assertRedirect();
        $callback = route('market.milestones.callback', ['Authority' => $first->refresh()->gateway_authority, 'Status' => 'OK']);

        $this->get($callback)->assertRedirect(route('market.contracts.show', $contract->uuid));
        $this->get($callback)->assertRedirect(route('market.contracts.show', $contract->uuid));

        $this->assertSame(MilestoneStatus::Funded, $first->refresh()->status);
        $this->assertSame(6_000_000, $this->balance(AccountType::ProjectEscrow));
    }

    public function test_an_unpaid_contract_lapses_and_the_project_reopens(): void
    {
        $rival = $this->provider('ali-rad', 'علی راد');
        $this->bidAs($rival);
        $contract = $this->accepted();

        Carbon::setTestNow(Carbon::now()->addDays(8));
        $this->artisan('marketplace:sweep')->assertSuccessful();

        $this->assertSame(ContractStatus::Lapsed, $contract->refresh()->status);
        $this->assertSame(ProjectStatus::Open, $this->project->refresh()->status);
        $this->assertTrue($this->project->acceptsBids());
        $this->assertSame(BidStatus::Active, MarketBid::query()->where('provider_user_id', $rival->id)->sole()->status);
        $this->assertSame(BidStatus::Declined, $contract->bid->refresh()->status);
        Carbon::setTestNow();
    }

    public function test_only_the_parties_see_the_contract_and_the_provider_gets_the_files_after_payment(): void
    {
        $first = $this->fundedFirstMilestone();
        $contract = $first->contract;

        $this->actingAs(User::factory()->create())->get(route('market.contracts.show', $contract->uuid))->assertNotFound();
        $this->actingAs($this->provider)->get(route('market.contracts.show', $contract->uuid))->assertOk()->assertSee('۱۰٪');
        $this->assertTrue(app(ProjectAccess::class)->canDownloadFiles($this->provider, $this->project));
    }

    public function test_switching_the_stream_off_stops_new_contracts_but_not_running_ones(): void
    {
        $first = $this->fundedFirstMilestone();
        $other = $this->publishedProject($this->client);
        $bid = $this->bidAs($this->provider, $other);

        app(ToggleRevenueStream::class)->handle(RevenueStream::ProjectMarketCommission, false);

        $this->actingAs($this->client)->post(route('market.contracts.accept', $bid->uuid))->assertSessionHasErrors('bid');
        $this->actingAs($this->provider)->post(route('market.milestones.deliver', $first->uuid), ['note' => 'تحویل با کلید خاموش'])->assertSessionHasNoErrors();
        $this->actingAs($this->client)->post(route('market.milestones.approve', $first->uuid))->assertSessionHasNoErrors();
        $this->assertSame(MilestoneStatus::Released, $first->refresh()->status);
    }

    private function bidAs(User $provider, ?MarketProject $project = null): MarketBid
    {
        $project ??= $this->project;
        $this->actingAs($provider)->post(route('market.bid.store', $project->id), $this->bidForm())->assertSessionHasNoErrors();

        return MarketBid::query()->where('project_id', $project->id)->where('provider_user_id', $provider->id)->sole();
    }

    private function accepted(): MarketContract
    {
        $bid = $this->bidAs($this->provider);
        $this->actingAs($this->client)->post(route('market.contracts.accept', $bid->uuid))->assertSessionHasNoErrors();

        return MarketContract::query()->sole();
    }

    private function fundedFirstMilestone(): MarketMilestone
    {
        $contract = $this->accepted();
        app(CreditWalletManually::class)->handle($this->client->id, Money::toman(20_000_000), null, idempotencyKey: (string) Str::uuid7());
        $this->actingAs($this->client)->post(route('market.milestones.pay', $contract->milestones[0]->uuid), ['payment' => 'wallet'])->assertSessionHasNoErrors();

        return $contract->milestones[0]->refresh();
    }

    private function balance(AccountType $type): int
    {
        return $this->app->make(LedgerBalanceReader::class)->balanceOf(new LedgerAccountRef($type))->toman;
    }
}
