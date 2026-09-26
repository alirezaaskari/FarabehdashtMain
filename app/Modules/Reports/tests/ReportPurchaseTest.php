<?php

declare(strict_types=1);

namespace App\Modules\Reports\Tests;

use App\Contracts\PaymentGateway;
use App\Contracts\WalletStatementReader;
use App\Models\User;
use App\Modules\Ledger\Actions\CreditWalletManually;
use App\Modules\Ledger\Domain\LedgerTransaction;
use App\Modules\Monetization\Actions\ToggleRevenueStream;
use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Reports\Actions\IssueReport;
use App\Modules\Reports\Actions\ReviseReport;
use App\Modules\Reports\Actions\UpdateReportPrice;
use App\Modules\Reports\Domain\Enums\ReportPurchaseStatus;
use App\Modules\Reports\Domain\Enums\ReportStatus;
use App\Modules\Reports\Domain\ReportPurchase;
use App\Modules\Reports\Services\ReportSale;
use App\Support\Entitlement\EntitlementDenied;
use App\Support\Money;
use App\Support\Payments\FakeZarinPalGateway;
use App\Support\Payments\PaymentSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * خرید تکی صدور گزارش (بخش ۱۸-۵، DEC-44).
 */
final class ReportPurchaseTest extends TestCase
{
    use RefreshDatabase;
    use ReportFixtures;

    private FakeZarinPalGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->gateway = new FakeZarinPalGateway;
        $this->app->instance(PaymentGateway::class, $this->gateway);
    }

    public function test_the_review_step_offers_the_single_report_price(): void
    {
        $user = User::factory()->create();
        $report = $this->draftFromProject($user);

        $this->actingAs($user)->get(route('reports.review', $report->uuid))
            ->assertOk()
            ->assertSee('یا فقط همین گزارش را بخرید')
            ->assertSee('۴۹٬۰۰۰');
    }

    public function test_paying_through_the_gateway_unlocks_issuing_that_report_only(): void
    {
        $user = User::factory()->create();
        $project = $this->project($user);
        $report = $this->draftFromProject($user, $project);
        $other = $this->draftFromProject($user, $project);

        $this->actingAs($user)->post(route('reports.purchase', $report->uuid))
            ->assertRedirect();

        $purchase = ReportPurchase::query()->where('report_id', $report->id)->sole();
        $this->assertSame(49_000, $purchase->price_toman);
        $this->assertNotNull($purchase->gateway_authority);

        $this->get(route('reports.purchase.callback', ['Authority' => $purchase->gateway_authority, 'Status' => 'OK']))
            ->assertRedirect(route('reports.review', $report->uuid));

        $this->assertSame(ReportPurchaseStatus::Paid, $purchase->fresh()?->status);
        $this->assertSame(1, LedgerTransaction::query()->where('kind', 'reports.purchase_paid')->count());

        $this->app->make(IssueReport::class)->handle($report, $user);
        $this->assertSame(ReportStatus::Issued, $report->fresh()?->status);

        $this->expectException(EntitlementDenied::class);
        $this->app->make(IssueReport::class)->handle($other, $user);
    }

    public function test_replaying_the_callback_does_not_record_the_ledger_twice(): void
    {
        $user = User::factory()->create();
        $report = $this->draftFromProject($user);

        $this->actingAs($user)->post(route('reports.purchase', $report->uuid));
        $params = ['Authority' => ReportPurchase::query()->sole()->gateway_authority, 'Status' => 'OK'];

        $this->get(route('reports.purchase.callback', $params))->assertRedirect();
        $this->get(route('reports.purchase.callback', $params))->assertRedirect();

        $this->assertSame(1, LedgerTransaction::query()->where('kind', 'reports.purchase_paid')->count());
    }

    public function test_a_cancelled_payment_leaves_the_report_locked_and_can_be_retried(): void
    {
        $user = User::factory()->create();
        $report = $this->draftFromProject($user);

        $this->actingAs($user)->post(route('reports.purchase', $report->uuid));
        $first = ReportPurchase::query()->sole();

        $this->get(route('reports.purchase.callback', ['Authority' => $first->gateway_authority, 'Status' => 'NOK']))
            ->assertSessionHasErrors('purchase');
        $this->assertSame(ReportPurchaseStatus::Failed, $first->fresh()?->status);
        $this->assertFalse($this->app->make(ReportSale::class)->covers($report));

        $this->post(route('reports.purchase', $report->uuid));
        $retry = ReportPurchase::query()->sole();

        $this->assertSame(ReportPurchaseStatus::Pending, $retry->status);
        $this->assertNotSame($first->gateway_authority, $retry->gateway_authority);
    }

    public function test_the_wallet_pays_without_the_gateway(): void
    {
        $user = User::factory()->create();
        $this->app->make(CreditWalletManually::class)->handle($user->id, Money::toman(100_000), null);
        $report = $this->draftFromProject($user);

        $this->actingAs($user)->post(route('reports.purchase', $report->uuid), ['payment' => 'wallet'])
            ->assertRedirect(route('reports.review', $report->uuid))
            ->assertSessionHas('status');

        $purchase = ReportPurchase::query()->sole();
        $this->assertSame(PaymentSource::Wallet, $purchase->payment_source);
        $this->assertSame(51_000, $this->app->make(WalletStatementReader::class)->balanceOf($user->id)->toman);

        $this->actingAs($user)->post(route('reports.issue', $report->uuid))
            ->assertRedirect(route('reports.show', $report->uuid));
    }

    public function test_an_insufficient_wallet_changes_nothing(): void
    {
        $user = User::factory()->create();
        $this->app->make(CreditWalletManually::class)->handle($user->id, Money::toman(10_000), null);
        $report = $this->draftFromProject($user);

        $this->actingAs($user)
            ->from(route('reports.review', $report->uuid))
            ->post(route('reports.purchase', $report->uuid), ['payment' => 'wallet'])
            ->assertSessionHasErrors('payment');

        $this->assertFalse($this->app->make(ReportSale::class)->covers($report));
        $this->assertSame(0, LedgerTransaction::query()->where('kind', 'reports.purchase_paid')->count());
    }

    public function test_a_revision_of_a_purchased_report_is_covered(): void
    {
        $user = User::factory()->create();
        $this->app->make(CreditWalletManually::class)->handle($user->id, Money::toman(100_000), null);
        $report = $this->draftFromProject($user);

        $this->actingAs($user)->post(route('reports.purchase', $report->uuid), ['payment' => 'wallet']);
        $this->app->make(IssueReport::class)->handle($report, $user);

        $revision = $this->app->make(ReviseReport::class)->handle($report->fresh() ?? $report);

        $this->assertTrue($this->app->make(ReportSale::class)->covers($revision));
        $this->app->make(IssueReport::class)->handle($revision, $user);
        $this->assertSame(ReportStatus::Issued, $revision->fresh()?->status);
    }

    public function test_turning_the_switch_off_closes_the_sale_but_keeps_what_was_bought(): void
    {
        $user = User::factory()->create();
        $this->app->make(CreditWalletManually::class)->handle($user->id, Money::toman(100_000), null);
        $project = $this->project($user);
        $bought = $this->draftFromProject($user, $project);
        $this->actingAs($user)->post(route('reports.purchase', $bought->uuid), ['payment' => 'wallet']);

        $this->app->make(ToggleRevenueStream::class)->handle(RevenueStream::PaidReportBuilder, false);

        $other = $this->draftFromProject($user, $project);
        $this->get(route('reports.review', $other->uuid))->assertOk()->assertDontSee('یا فقط همین گزارش را بخرید');
        $this->post(route('reports.purchase', $other->uuid))->assertRedirect(route('home'));

        $this->app->make(IssueReport::class)->handle($bought, $user);
        $this->assertSame(ReportStatus::Issued, $bought->fresh()?->status);
    }

    public function test_the_price_comes_from_the_panel_and_past_purchases_keep_theirs(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $report = $this->draftFromProject($user);

        $this->actingAs($user)->post(route('reports.purchase', $report->uuid));

        $this->app->make(UpdateReportPrice::class)->handle(Money::toman(59_000), $admin->id);

        $this->assertSame(59_000, $this->app->make(ReportSale::class)->price()->toman);
        $this->assertSame(49_000, ReportPurchase::query()->sole()->price_toman);
        $this->assertDatabaseHas('audit_logs', ['action' => 'reports.price_changed']);
    }

    public function test_a_zero_price_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->app->make(UpdateReportPrice::class)->handle(Money::zero(), 1);
    }

    public function test_someone_elses_report_cannot_be_bought(): void
    {
        $owner = User::factory()->create();
        $report = $this->draftFromProject($owner);

        $this->actingAs(User::factory()->create())
            ->post(route('reports.purchase', $report->uuid))
            ->assertNotFound();
    }
}
