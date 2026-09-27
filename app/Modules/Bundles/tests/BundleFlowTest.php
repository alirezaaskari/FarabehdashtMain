<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Tests;

use App\Contracts\EntitlementGate;
use App\Contracts\LedgerBalanceReader;
use App\Contracts\PaymentGateway;
use App\Contracts\WalletStatementReader;
use App\Models\User;
use App\Modules\Bundles\Actions\SaveBundle;
use App\Modules\Bundles\Domain\BundlePurchase;
use App\Modules\Bundles\Domain\Enums\PurchaseStatus;
use App\Modules\Bundles\Refunds\BundlePurchaseRefunds;
use App\Modules\Commerce\Services\ProductAccess;
use App\Modules\Courses\Domain\Enrollment;
use App\Modules\Courses\Domain\Enums\EnrollmentStatus;
use App\Modules\Ledger\Actions\CreditWalletManually;
use App\Modules\Ledger\Domain\LedgerEntry;
use App\Modules\Ledger\Domain\LedgerTransaction;
use App\Modules\Monetization\Actions\ToggleRevenueStream;
use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Monetization\Domain\Subscription;
use App\Support\Entitlement\EntitlementReason;
use App\Support\Entitlement\Feature;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Money;
use App\Support\Payments\FakeZarinPalGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/** بسته راه‌حل: یک خرید، سهم هر صاحب جزء، و فعال شدن همه اجزا (بخش ۱۸-۸). */
final class BundleFlowTest extends TestCase
{
    use BundleFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(PaymentGateway::class, new FakeZarinPalGateway);
    }

    public function test_bundle_price_must_be_below_the_sum_of_items(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->bundle(price: '1110000');
    }

    public function test_a_bundle_needs_at_least_two_items(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->app->make(SaveBundle::class)->handle([
            'title' => 'بسته تک‌جزئی',
            'slug' => 'single',
            'description' => 'فقط یک جزء.',
            'price' => '100000',
            'items' => ['pro:1'],
        ], User::factory()->create()->id);
    }

    public function test_catalog_lists_published_bundles_with_saving(): void
    {
        $bundle = $this->bundle();

        $this->get(route('bundles.index'))->assertOk()->assertSee($bundle->title)->assertSee('۹۹۹٬۰۰۰');
        $this->get(route('bundles.show', $bundle->slug))
            ->assertOk()
            ->assertSee($this->product->title)
            ->assertSee($this->course->title)
            ->assertSee('۳ ماه اشتراک حرفه‌ای')
            ->assertSee('۱۱۱٬۰۰۰');
    }

    public function test_draft_bundle_is_hidden(): void
    {
        $bundle = $this->bundle(publish: false);

        $this->get(route('bundles.index'))->assertOk()->assertDontSee($bundle->title);
        $this->get(route('bundles.show', $bundle->slug))->assertNotFound();
    }

    public function test_wallet_purchase_splits_money_and_grants_every_item(): void
    {
        $bundle = $this->bundle();
        $buyer = User::factory()->create();
        $this->app->make(CreditWalletManually::class)->handle($buyer->id, Money::toman(1_000_000), null);

        $this->actingAs($buyer)
            ->post(route('bundles.purchase', $bundle->slug), ['payment' => 'wallet'])
            ->assertRedirect(route('bundles.show', $bundle->slug));

        $purchase = BundlePurchase::query()->where('user_id', $buyer->id)->sole();
        $this->assertSame(PurchaseStatus::Paid, $purchase->status);
        $this->assertSame(1_000, $this->app->make(WalletStatementReader::class)->balanceOf($buyer->id)->toman);

        // سهم‌ها به نسبت قیمت: فایل ۸۱ هزار، دوره ۱۳۵ هزار، حرفه‌ای ۷۸۳ هزار؛ کارمزد ۲۰٪.
        $balances = $this->app->make(LedgerBalanceReader::class);
        $this->assertSame(64_800, $balances->balanceOf(LedgerAccountRef::vendorPayable($this->product->vendor_user_id))->toman);
        $this->assertSame(108_000, $balances->balanceOf(LedgerAccountRef::vendorPayable($this->course->instructor_user_id))->toman);

        $transaction = LedgerTransaction::query()->where('reference_id', $purchase->uuid)->sole();
        $entries = LedgerEntry::query()->where('transaction_id', $transaction->id)->get();
        $this->assertSame(999_000, (int) $entries->where('direction', EntryDirection::Debit)->sum('amount_toman'));
        $this->assertSame(999_000, (int) $entries->where('direction', EntryDirection::Credit)->sum('amount_toman'));

        $this->assertTrue($this->app->make(ProductAccess::class)->userOwns($buyer->id, $this->product));
        $this->assertSame(
            EnrollmentStatus::Paid,
            Enrollment::query()->where('course_id', $this->course->id)->where('student_user_id', $buyer->id)->sole()->status,
        );
        $this->assertSame(
            EntitlementReason::Subscribed,
            $this->app->make(EntitlementGate::class)->decide($buyer->refresh(), Feature::BuildReport)->reason,
        );

        $this->actingAs($buyer)->get(route('bundles.show', $bundle->slug))->assertOk()->assertSee('خریده‌اید');
    }

    public function test_gateway_purchase_completes_once_on_repeated_callback(): void
    {
        $bundle = $this->bundle();
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->post(route('bundles.purchase', $bundle->slug))->assertRedirect();

        $purchase = BundlePurchase::query()->where('user_id', $buyer->id)->sole();
        $callback = route('bundles.purchase.callback', ['Authority' => $purchase->gateway_authority, 'Status' => 'OK']);

        $this->get($callback)->assertRedirect(route('bundles.show', $bundle->slug));
        $this->get($callback)->assertRedirect(route('bundles.show', $bundle->slug));

        $this->assertTrue($purchase->refresh()->isPaid());
        $this->assertSame(1, LedgerTransaction::query()->where('reference_id', $purchase->uuid)->count());
    }

    public function test_closed_sales_switch_blocks_purchase(): void
    {
        $bundle = $this->bundle();
        $this->app->make(ToggleRevenueStream::class)->handle(RevenueStream::SolutionBundle, false);

        $this->actingAs(User::factory()->create())
            ->post(route('bundles.purchase', $bundle->slug), ['payment' => 'wallet'])
            ->assertRedirect();

        $this->assertSame(0, BundlePurchase::query()->count());
    }

    public function test_a_refund_reverses_every_share_and_takes_the_items_back(): void
    {
        $bundle = $this->bundle();
        $buyer = User::factory()->create();
        $this->app->make(CreditWalletManually::class)->handle($buyer->id, Money::toman(1_000_000), null);
        $this->actingAs($buyer)->post(route('bundles.purchase', $bundle->slug), ['payment' => 'wallet']);
        $purchase = BundlePurchase::query()->where('user_id', $buyer->id)->sole();

        $this->assertSame(999_000, $this->app->make(BundlePurchaseRefunds::class)->refund($purchase->uuid, User::factory()->create()->id, 'بسته اشتباه انتخاب شد')->toman);

        $this->assertSame(PurchaseStatus::Refunded, $purchase->refresh()->status);
        $this->assertSame(1_000_000, $this->app->make(WalletStatementReader::class)->balanceOf($buyer->id)->toman);
        $this->assertSame(0, $this->app->make(LedgerBalanceReader::class)->balanceOf(LedgerAccountRef::vendorPayable($this->product->vendor_user_id))->toman);
        $this->assertSame(0, $this->app->make(LedgerBalanceReader::class)->balanceOf(LedgerAccountRef::vendorPayable($this->course->instructor_user_id))->toman);
        $this->assertSame(0, $this->app->make(LedgerBalanceReader::class)->balanceOf(new LedgerAccountRef(AccountType::PlatformRevenue))->toman);

        $this->assertFalse($this->app->make(ProductAccess::class)->userOwns($buyer->id, $this->product));
        $this->assertSame(EnrollmentStatus::Refunded, Enrollment::query()->where('student_user_id', $buyer->id)->sole()->status);
        $this->assertFalse(Subscription::query()->where('user_id', $buyer->id)->sole()->isCurrent());
    }
}
