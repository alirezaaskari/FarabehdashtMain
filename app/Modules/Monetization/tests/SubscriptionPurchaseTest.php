<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Tests;

use App\Contracts\EntitlementGate;
use App\Contracts\PaymentGateway;
use App\Models\User;
use App\Modules\Ledger\Domain\LedgerEntry;
use App\Modules\Ledger\Domain\LedgerTransaction;
use App\Modules\Monetization\Domain\Enums\PeriodStatus;
use App\Modules\Monetization\Domain\Enums\SubscriptionStatus;
use App\Modules\Monetization\Domain\Subscription;
use App\Modules\Monetization\Domain\SubscriptionPeriod;
use App\Modules\Monetization\Services\Payments\FakeSubscriptionGateway;
use App\Modules\Monetization\Services\PlanCatalog;
use App\Support\Entitlement\EntitlementReason;
use App\Support\Entitlement\Feature;
use App\Support\Ledger\EntryDirection;
use App\Support\Payments\PaymentGatewayUnavailable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\UnavailablePaymentGateway;
use Tests\TestCase;

/**
 * خرید، تمدید و اثر مالی اشتراک.
 *
 * اشتراک درآمد خود پلتفرم است، پس هیچ ردیف `vendor_payable` ندارد و از
 * سرویس کمیسیون عبور نمی‌کند — برخلاف فروش فایل و دوره.
 */
final class SubscriptionPurchaseTest extends TestCase
{
    use RefreshDatabase;

    private FakeSubscriptionGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new FakeSubscriptionGateway;
        $this->app->instance(PaymentGateway::class, $this->gateway);
    }

    public function test_a_paid_subscription_records_a_balanced_ledger_transaction_and_opens_every_feature(): void
    {
        $user = User::factory()->create();

        $period = $this->buy($user, 'pro-monthly');

        $this->assertSame(PeriodStatus::Paid, $period->status);
        $this->assertSame(290_000, $period->price_toman);

        $transaction = LedgerTransaction::query()
            ->where('reference_id', $period->uuid)
            ->firstOrFail();

        $entries = LedgerEntry::query()->where('transaction_id', $transaction->id)->get();

        $this->assertCount(2, $entries);
        $this->assertSame(
            $entries->where('direction', EntryDirection::Debit)->sum('amount_toman'),
            $entries->where('direction', EntryDirection::Credit)->sum('amount_toman'),
        );
        $this->assertSame(290_000, (int) $entries->where('direction', EntryDirection::Credit)->sum('amount_toman'));

        $this->assertSame(
            EntitlementReason::Subscribed,
            $this->app->make(EntitlementGate::class)->decide($user->refresh(), Feature::BuildReport)->reason,
        );
    }

    /** Callback تکراری زرین‌پال هرگز اثر مالی دوم نمی‌گذارد. */
    public function test_a_repeated_callback_has_no_second_effect(): void
    {
        $user = User::factory()->create();
        $period = $this->buy($user, 'pro-monthly');

        $this->get(route('monetization.callback', [
            'Authority' => $period->gateway_authority,
            'Status' => 'OK',
        ]))->assertOk();

        $this->assertSame(1, LedgerTransaction::query()->where('reference_id', $period->uuid)->count());

        $subscription = Subscription::query()->where('user_id', $user->getKey())->firstOrFail();
        $this->assertTrue($subscription->ends_at?->isBefore(Carbon::now()->addMonth()->addDay()));
    }

    /** تمدید زودهنگام روزهای باقی‌مانده را نمی‌سوزاند. */
    public function test_renewing_early_extends_from_the_current_end_date(): void
    {
        $user = User::factory()->create();

        $first = $this->buy($user, 'pro-monthly');
        $firstEnd = $first->ends_at;
        $this->assertNotNull($firstEnd);

        $second = $this->buy($user, 'pro-monthly');

        $this->assertNotNull($second->starts_at);
        $this->assertSame($firstEnd->toIso8601String(), $second->starts_at->toIso8601String());

        $subscription = Subscription::query()->where('user_id', $user->getKey())->firstOrFail();
        $this->assertSame($second->ends_at?->toIso8601String(), $subscription->ends_at?->toIso8601String());
    }

    public function test_a_failed_verification_leaves_no_financial_trace(): void
    {
        $user = User::factory()->create();
        $plan = $this->app->make(PlanCatalog::class)->findBySlug('pro-monthly');
        $this->assertNotNull($plan);

        $this->actingAs($user)->post(route('monetization.checkout', $plan->slug))->assertRedirect();

        $period = SubscriptionPeriod::query()->latest('id')->firstOrFail();
        $this->gateway->failNextVerification();

        $this->get(route('monetization.callback', [
            'Authority' => $period->gateway_authority,
            'Status' => 'OK',
        ]))->assertOk();

        $this->assertSame(PeriodStatus::Failed, $period->refresh()->status);
        $this->assertSame(0, LedgerTransaction::query()->count());
    }

    /** لغو فقط تمدید خودکار را می‌گیرد، نه دسترسی این دوره را. */
    public function test_cancelling_keeps_access_until_the_period_ends(): void
    {
        $user = User::factory()->create();
        $this->buy($user, 'pro-monthly');

        $this->actingAs($user)->post(route('monetization.cancel'))->assertRedirect(route('monetization.plans'));

        $subscription = Subscription::query()->where('user_id', $user->getKey())->firstOrFail();

        $this->assertSame(SubscriptionStatus::Cancelled, $subscription->status);
        $this->assertTrue($subscription->isCurrent());
        $this->assertSame(
            EntitlementReason::Subscribed,
            $this->app->make(EntitlementGate::class)->decide($user, Feature::BuildReport)->reason,
        );
    }

    private function buy(User $user, string $slug): SubscriptionPeriod
    {
        $plan = $this->app->make(PlanCatalog::class)->findBySlug($slug);
        $this->assertNotNull($plan);

        $this->actingAs($user)->post(route('monetization.checkout', $plan->slug))->assertRedirect();

        $period = SubscriptionPeriod::query()->latest('id')->firstOrFail();

        $this->get(route('monetization.callback', [
            'Authority' => $period->gateway_authority,
            'Status' => 'OK',
        ]))->assertOk();

        return $period->refresh();
    }

    public function test_an_unreachable_gateway_shows_a_failure_page_instead_of_an_error(): void
    {
        $this->app->instance(PaymentGateway::class, new UnavailablePaymentGateway);
        $plan = $this->app->make(PlanCatalog::class)->findBySlug('pro-monthly');
        $this->assertNotNull($plan);

        $this->actingAs(User::factory()->create())
            ->post(route('monetization.checkout', $plan->slug))
            ->assertOk()
            ->assertSee(PaymentGatewayUnavailable::USER_MESSAGE)
            ->assertDontSee('ZARINPAL_MERCHANT_ID');

        $this->assertSame(PeriodStatus::Failed, SubscriptionPeriod::query()->sole()->status);
    }
}
