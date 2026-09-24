<?php

declare(strict_types=1);

namespace Tests\Feature\Acceptance;

use App\Contracts\CommissionCalculator;
use App\Contracts\EntitlementGate;
use App\Contracts\LedgerBalanceReader;
use App\Contracts\PaymentGateway;
use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Commerce\Domain\Enums\OrderStatus;
use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Order;
use App\Modules\Commerce\Domain\Product;
use App\Modules\Commerce\Filament\Pages\RefundPage;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enrollment;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use App\Modules\Courses\Domain\Enums\EnrollmentStatus;
use App\Modules\Ledger\Domain\LedgerEntry;
use App\Modules\Ledger\Domain\LedgerTransaction;
use App\Modules\Ledger\Domain\Wallet;
use App\Modules\Ledger\Filament\Pages\WalletTopupPage;
use App\Modules\Monetization\Actions\StartSubscriptionCheckout;
use App\Modules\Monetization\Domain\Enums\PeriodStatus;
use App\Modules\Monetization\Domain\SubscriptionPeriod;
use App\Modules\Monetization\Services\PlanCatalog;
use App\Modules\Reports\Actions\IssueReport;
use App\Modules\Reports\Domain\Enums\ReportStatus;
use App\Modules\Reports\Tests\ReportFixtures;
use App\Support\Entitlement\EntitlementDenied;
use App\Support\Entitlement\EntitlementReason;
use App\Support\Entitlement\Feature;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Payments\FakeZarinPalGateway;
use App\Support\Payments\FinancialActionBlocked;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * تست پذیرش مالی نسخه ۱ — ردیف «مالی» بخش ۱۷-ب نقشه راه.
 *
 * یک کاربر، یک مسیر کامل: شارژ دستی کیف پول، خرید فایل، بازگشت وجه، خرید
 * اشتراک Pro، خرید با تخفیف مشترک، ثبت‌نام دوره و صدور گزارش. همه از راه همان
 * Action، مسیر HTTP یا صفحه پنلی که کاربر و مدیر واقعی به‌کار می‌برند.
 *
 * پس از **هر** گام ناورداهای ADR-0003 سنجیده می‌شوند: جمع هر تراکنش صفر است،
 * موجودی هر کیف پول با جمع ردیف‌های دفتر کل برابر است و هیچ کیف پولی منفی نیست.
 */
final class FinancialScenarioTest extends TestCase
{
    use RefreshDatabase;
    use ReportFixtures;

    private FakeZarinPalGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        // یک درگاه ساختگی برای هر سه جریان پرداخت (فروشگاه، اشتراک، دوره).
        $this->gateway = new FakeZarinPalGateway;
        $this->app->instance(PaymentGateway::class, $this->gateway);

        Storage::fake('local');
    }

    public function test_one_buyer_walks_through_every_financial_flow_and_the_ledger_always_balances(): void
    {
        $finance = $this->admin(AdminRole::Finance);
        $buyer = User::factory()->create();
        $vendor = User::factory()->create();
        $instructor = User::factory()->create();

        $this->assertLedgerInvariants('پیش از شروع');

        // صفحه‌های پنل بیرون از درخواست HTTP، پنل جاری را خودشان نمی‌شناسند.
        Filament::setCurrentPanel(Filament::getPanel('fbh'));

        // ─── گام ۱: مدیر مالی کیف پول خریدار را از صفحه شارژ دستی پنل شارژ می‌کند ───
        Livewire::actingAs($finance)
            ->test(WalletTopupPage::class)
            ->set('mobile', $buyer->mobile)
            ->set('amount', '500000')
            ->set('memo', 'واریز بانکی آزمون پذیرش')
            ->call('credit')
            ->assertSet('error', null);

        $this->assertSame(500_000, $this->walletOf($buyer));
        $topup = LedgerTransaction::query()->where('kind', 'wallet.manual_topup')->sole();
        $this->assertSame($finance->id, $topup->created_by);
        $this->assertLedgerInvariants('گام ۱ — شارژ دستی');

        // ─── گام ۲: خرید فایل از فروشنده با درگاه؛ کمیسیون از نرخ واقعی تنظیمات ───
        $rateBp = $this->app->make(CommissionCalculator::class)->currentRateBp('shop');
        $this->assertSame((int) config('commerce.commission.default_rate_bp.shop'), $rateBp);
        $this->assertSame(2000, $rateBp, 'تصمیم مدیر (ADR-0003): کمیسیون فروشگاه ۲۰٪.');

        $firstProduct = $this->publishedProduct($vendor, 100_000);
        $firstOrder = $this->buyThroughGateway($buyer, $firstProduct);

        $expectedCommission = intdiv(100_000 * $rateBp, 10_000);
        $firstItem = $firstOrder->items()->sole();

        $this->assertSame(OrderStatus::Paid, $firstOrder->status);
        $this->assertSame(100_000, $firstOrder->total_toman);
        $this->assertSame($expectedCommission, $firstItem->commission_toman);
        $this->assertSame(100_000 - $expectedCommission, $firstItem->vendor_amount_toman);
        $this->assertSame($expectedCommission, $this->platformRevenue());
        $this->assertSame(100_000 - $expectedCommission, $this->vendorPayable($vendor));
        $this->assertSame(500_000, $this->walletOf($buyer), 'خرید از درگاه به کیف پول دست نمی‌زند.');
        $this->assertLedgerInvariants('گام ۲ — خرید فایل');

        // ─── گام ۳: مدیر مالی از صفحه بازگشت وجه، کل مبلغ را به کیف پول برمی‌گرداند ───
        Livewire::actingAs($finance)
            ->test(RefundPage::class)
            ->set('orderUuid', $firstOrder->uuid)
            ->call('find')
            ->assertSet('error', null)
            ->set('amounts.'.$firstItem->id, '100000')
            ->call('preview', $firstItem->id)
            ->call('confirm', $firstItem->id);

        $this->assertSame(OrderStatus::Refunded, $firstOrder->fresh()?->status);
        $this->assertSame(600_000, $this->walletOf($buyer));
        $this->assertSame(0, $this->platformRevenue(), 'کمیسیون سفارش بازگشتی کامل برمی‌گردد.');
        $this->assertSame(0, $this->vendorPayable($vendor), 'بدهی به فروشنده کامل صفر می‌شود.');
        $this->assertLedgerInvariants('گام ۳ — بازگشت وجه');

        // ─── گام ۴: خرید اشتراک Pro از درگاه ───
        $plan = $this->app->make(PlanCatalog::class)->findBySlug('pro-monthly');
        $this->assertNotNull($plan);

        $this->actingAs($buyer)->post(route('monetization.checkout', $plan->slug))->assertRedirect();
        $period = SubscriptionPeriod::query()->latest('id')->firstOrFail();
        $this->get(route('monetization.callback', [
            'Authority' => $period->gateway_authority,
            'Status' => 'OK',
        ]))->assertOk();

        $this->assertSame(PeriodStatus::Paid, $period->refresh()->status);
        $this->assertSame($plan->price_toman, $period->price_toman);
        $this->assertSame(
            EntitlementReason::Subscribed,
            $this->app->make(EntitlementGate::class)->decide($buyer->refresh(), Feature::ShopDiscount)->reason,
        );
        $revenueAfterPro = $plan->price_toman;
        $this->assertSame($revenueAfterPro, $this->platformRevenue(), 'اشتراک تماماً درآمد پلتفرم است.');
        $this->assertLedgerInvariants('گام ۴ — خرید Pro');

        // ─── گام ۵: خرید دوم با تخفیف مشترک؛ تخفیف از کمیسیون پلتفرم کم می‌شود ───
        $discountPercent = (int) config('monetization.pro_discount_percent');
        $this->assertSame(10, $discountPercent);

        $secondProduct = $this->publishedProduct($vendor, 200_000);
        $secondOrder = $this->buyThroughGateway($buyer, $secondProduct);
        $secondItem = $secondOrder->items()->sole();

        $listCommission = intdiv(200_000 * $rateBp, 10_000);
        $discount = min(intdiv(200_000 * $discountPercent, 100), $listCommission);

        $this->assertSame(OrderStatus::Paid, $secondOrder->status);
        $this->assertSame($discount, $secondItem->discount_toman);
        $this->assertSame(200_000 - $discount, $secondOrder->total_toman);
        $this->assertSame($listCommission - $discount, $secondItem->commission_toman);
        $this->assertSame(200_000 - $listCommission, $secondItem->vendor_amount_toman, 'سهم فروشنده با تخفیف کم نمی‌شود.');
        $this->assertSame(
            $secondOrder->total_toman,
            $secondItem->commission_toman + $secondItem->vendor_amount_toman,
        );
        $this->assertSame($revenueAfterPro + $listCommission - $discount, $this->platformRevenue());
        $this->assertSame(200_000 - $listCommission, $this->vendorPayable($vendor));
        $this->assertLedgerInvariants('گام ۵ — خرید با تخفیف مشترک');

        // ─── گام ۶: ثبت‌نام در دوره پولی (تخفیف مشترک فقط روی فایل است، نه دوره) ───
        $courseRateBp = $this->app->make(CommissionCalculator::class)->currentRateBp('course');
        $course = $this->publishedCourse($instructor, 300_000);

        $this->actingAs($buyer)->post(route('courses.enroll', $course))->assertRedirect();
        $enrollment = Enrollment::query()->where('course_id', $course->id)->sole();
        $this->get(route('courses.callback', [
            'Authority' => $enrollment->gateway_authority,
            'Status' => 'OK',
        ]))->assertOk();

        $enrollment->refresh();
        $courseCommission = intdiv(300_000 * $courseRateBp, 10_000);

        $this->assertSame(EnrollmentStatus::Paid, $enrollment->status);
        $this->assertSame(300_000, $enrollment->price_toman);
        $this->assertSame($courseCommission, $enrollment->commission_toman);
        $this->assertSame(300_000 - $courseCommission, $this->vendorPayable($instructor), 'مدرس همان حساب بدهی فروشنده را دارد.');
        $this->assertSame($revenueAfterPro + $listCommission - $discount + $courseCommission, $this->platformRevenue());
        $this->assertLedgerInvariants('گام ۶ — ثبت‌نام دوره');

        // ─── گام ۷: صدور گزارش فقط برای مشترک؛ کاربر رایگان رد می‌شود ───
        $freeUser = User::factory()->create();
        $freeDraft = $this->draftFromProject($freeUser);

        $this->actingAs($freeUser)->post(route('reports.issue', $freeDraft->uuid))->assertRedirect();
        $this->assertSame(ReportStatus::Draft, $freeDraft->fresh()?->status, 'کاربر رایگان گزارش صادر نمی‌کند.');

        try {
            $this->app->make(IssueReport::class)->handle($freeDraft, $freeUser);
            $this->fail('صدور گزارش برای کاربر رایگان باید رد شود.');
        } catch (EntitlementDenied) {
            // همان چیزی که انتظار می‌رفت.
        }

        $draft = $this->draftFromProject($buyer);

        $this->actingAs($buyer)->post(route('reports.issue', $draft->uuid))
            ->assertRedirect(route('reports.show', $draft->uuid));

        $this->assertSame(ReportStatus::Issued, $draft->fresh()?->status);
        $this->assertLedgerInvariants('گام ۷ — صدور گزارش');

        // جمع‌بندی سفر: کیف پول خریدار فقط شارژ دستی و بازگشت وجه را دیده است.
        $this->assertSame(600_000, $this->walletOf($buyer));
    }

    /**
     * ریال فقط داخل آداپتور درگاه دیده می‌شود (CLAUDE.md، ADR-0003).
     *
     * توکن‌های PHP بررسی می‌شوند، نه متن خام، تا ارجاع در توضیحات (مثل
     * `app/Contracts/PaymentGateway.php`) فراخوان حساب نشود.
     */
    public function test_rial_conversion_is_called_only_by_money_and_the_gateway_adapters(): void
    {
        $root = dirname(__DIR__, 3);
        $allowed = static fn (string $relative): bool => $relative === 'app/Support/Money.php'
            || in_array($relative, ['app/Support/Payments/ZarinPalGateway.php', 'app/Support/Payments/FakeZarinPalGateway.php'], true);

        $offenders = [];
        $scanned = 0;

        foreach (['app', 'packages'] as $dir) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$dir, RecursiveDirectoryIterator::SKIP_DOTS));

            /** @var SplFileInfo $file */
            foreach ($files as $file) {
                $relative = substr($file->getPathname(), strlen($root) + 1);

                if ($file->getExtension() !== 'php' || preg_match('#/(tests|vendor)/#', '/'.$relative) === 1) {
                    continue;
                }

                $scanned++;

                if ($this->callsRialConversion((string) file_get_contents($file->getPathname())) && ! $allowed($relative)) {
                    $offenders[] = $relative;
                }
            }
        }

        $this->assertGreaterThan(100, $scanned, 'پیمایش باید واقعاً کد برنامه را دیده باشد.');
        $this->assertSame([], $offenders, 'toRialForGateway() فقط در Money و آداپتور درگاه مجاز است.');

        $adapter = (string) file_get_contents($root.'/app/Support/Payments/ZarinPalGateway.php');
        $this->assertTrue($this->callsRialConversion($adapter), 'آداپتور واقعی باید همین راه را به‌کار ببرد.');
    }

    /** در حالت «مشاهده به‌عنوان کاربر» هیچ مسیر مالی کاربر پذیرفته نمی‌شود. */
    public function test_no_financial_route_works_while_an_admin_views_the_site_as_a_user(): void
    {
        $super = $this->admin(AdminRole::Super);
        $target = User::factory()->create();

        $product = $this->publishedProduct(User::factory()->create(), 100_000);
        $course = $this->publishedCourse(User::factory()->create(), 300_000);
        $plan = $this->app->make(PlanCatalog::class)->findBySlug('pro-monthly');
        $this->assertNotNull($plan);

        $this->actingAs($super)
            ->post(route('admin.impersonate.start', $target), ['reason' => 'بررسی گزارش خطای کاربر در سبد خرید'])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($target);

        $this->post(route('commerce.cart.add', $product));
        $this->assertRefused($this->post(route('commerce.checkout')), 'پرداخت سبد خرید');
        $this->assertRefused($this->post(route('monetization.checkout', $plan->slug)), 'خرید اشتراک');
        $this->assertRefused($this->post(route('courses.enroll', $course)), 'ثبت‌نام دوره');
        $this->assertRefused($this->post(route('monetization.cancel')), 'لغو تمدید اشتراک');

        $this->assertSame(0, Order::query()->count(), 'هیچ سفارشی ساخته نمی‌شود.');
        $this->assertSame(0, SubscriptionPeriod::query()->count(), 'هیچ دوره اشتراکی ساخته نمی‌شود.');
        $this->assertSame(0, Enrollment::query()->count(), 'هیچ ثبت‌نامی ساخته نمی‌شود.');
        $this->assertSame(0, LedgerTransaction::query()->count(), 'هیچ چیزی در دفتر کل نوشته نمی‌شود.');
        $this->assertLedgerInvariants('مشاهده به‌عنوان کاربر');
    }

    public function test_the_payment_actions_refuse_on_their_own_even_without_the_route_guard(): void
    {
        // میان‌افزار فقط مسیرها را می‌پاید؛ هر فراخوان دیگری از Action (فرمان،
        // صف، کنترلر آینده) هم باید پیش از درگاه بایستد.
        $target = User::factory()->create();
        $plan = $this->app->make(PlanCatalog::class)->findBySlug('pro-monthly');
        $this->assertNotNull($plan);

        $this->actingAs($this->admin(AdminRole::Super))
            ->post(route('admin.impersonate.start', $target), ['reason' => 'بررسی گزارش خطای کاربر در اشتراک']);

        try {
            $this->app->make(StartSubscriptionCheckout::class)->handle($target, $plan);
            $this->fail('شروع پرداخت اشتراک در حالت مشاهده باید رد شود.');
        } catch (FinancialActionBlocked) {
            $this->assertSame(0, SubscriptionPeriod::query()->count());
        }
    }

    // ─── ناورداهای دفتر کل ───

    /**
     * سه ناوردای ADR-0003، پس از هر گام:
     *
     * ۱. جمع علامت‌دار ردیف‌های هر تراکنش صفر است؛
     * ۲. موجودی کش‌شده هر کیف پول با جمع ردیف‌های حسابش در دفتر کل برابر است
     *    (هم با پرسش مستقیم، هم با خواننده موجودی و هم با دستور تطبیق)؛
     * ۳. هیچ کیف پولی منفی نیست.
     */
    private function assertLedgerInvariants(string $step): void
    {
        $net = [];

        foreach (LedgerEntry::query()->get() as $entry) {
            $net[$entry->transaction_id] = ($net[$entry->transaction_id] ?? 0) + $entry->amount_toman * $entry->direction->sign();
        }

        foreach (LedgerTransaction::query()->get() as $transaction) {
            $this->assertArrayHasKey($transaction->id, $net, "{$step}: تراکنش {$transaction->kind} ردیفی ندارد.");
            $this->assertSame(0, $net[$transaction->id], "{$step}: جمع تراکنش {$transaction->kind} صفر نیست.");
        }

        $reader = $this->app->make(LedgerBalanceReader::class);

        foreach (Wallet::query()->get() as $wallet) {
            $fromEntries = 0;

            foreach (LedgerEntry::query()->where('account_id', $wallet->ledger_account_id)->get() as $entry) {
                $fromEntries += $entry->amount_toman * $entry->direction->sign();
            }

            $this->assertSame($fromEntries, $wallet->cached_balance_toman, "{$step}: کش کیف پول با دفتر کل نمی‌خواند.");
            $this->assertSame($fromEntries, $reader->balanceOf(LedgerAccountRef::wallet($wallet->user_id))->toman, "{$step}: خواننده موجودی با دفتر کل نمی‌خواند.");
            $this->assertGreaterThanOrEqual(0, $wallet->cached_balance_toman, "{$step}: کیف پول منفی شد.");
        }

        $this->artisan('fbh:reconcile-wallets')->assertExitCode(0);
    }

    // ─── ابزار ساخت داده ───

    private function admin(AdminRole $role): User
    {
        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, $role);

        return $user->fresh() ?? $user;
    }

    private function publishedProduct(User $vendor, int $priceToman): Product
    {
        return Product::query()->create([
            'uuid' => (string) Str::uuid7(),
            'vendor_user_id' => $vendor->id,
            'slug' => 'p-'.Str::random(8),
            'title' => 'فایل آموزشی آزمون پذیرش',
            'price_toman' => $priceToman,
            'status' => ProductStatus::Published,
        ])->refresh();
    }

    private function publishedCourse(User $instructor, int $priceToman): Course
    {
        return Course::query()->create([
            'uuid' => (string) Str::uuid7(),
            'instructor_user_id' => $instructor->id,
            'slug' => 'c-'.Str::random(8),
            'title' => 'دوره آزمون پذیرش',
            'price_toman' => $priceToman,
            'status' => CourseStatus::Published,
        ])->refresh();
    }

    /** سبد، پرداخت و بازگشت موفق از درگاه — همان مسیر HTTP کاربر واقعی. */
    private function buyThroughGateway(User $buyer, Product $product): Order
    {
        $this->actingAs($buyer)->post(route('commerce.cart.add', $product))->assertRedirect();
        $this->actingAs($buyer)->post(route('commerce.checkout'))->assertRedirect();

        $order = Order::query()->forBuyer($buyer->id)->latest('id')->firstOrFail();
        $this->assertSame(OrderStatus::Pending, $order->status);

        $this->get(route('commerce.callback', [
            'Authority' => $order->gateway_authority,
            'Status' => 'OK',
        ]))->assertOk();

        return $order->refresh();
    }

    private function walletOf(User $user): int
    {
        return Wallet::query()->where('user_id', $user->id)->firstOrFail()->cached_balance_toman;
    }

    private function platformRevenue(): int
    {
        return $this->app->make(LedgerBalanceReader::class)
            ->balanceOf(new LedgerAccountRef(AccountType::PlatformRevenue))->toman;
    }

    private function vendorPayable(User $vendor): int
    {
        return $this->app->make(LedgerBalanceReader::class)
            ->balanceOf(LedgerAccountRef::vendorPayable($vendor->id))->toman;
    }

    /**
     * رد یعنی کاربر به درگاه فرستاده نمی‌شود؛ اینکه پاسخ ۴۰۳ باشد یا بازگشت با
     * پیام خطا، تصمیم لایه نمایش است. نبودِ اثر در دیتابیس جداگانه سنجیده می‌شود.
     *
     * @param  TestResponse<Response>  $response
     */
    private function assertRefused(TestResponse $response, string $what): void
    {
        $location = (string) $response->headers->get('Location', '');

        $this->assertStringNotContainsString('fake-gateway.test', $location, "{$what}: نباید به درگاه هدایت شود.");
        $this->assertSame(403, $response->getStatusCode(), "{$what}: رد باید آگاهانه باشد، نه خطای سرور.");
    }

    private function callsRialConversion(string $source): bool
    {
        $tokens = token_get_all($source);

        foreach ($tokens as $index => $token) {
            if (! is_array($token) || $token[0] !== T_STRING || $token[1] !== 'toRialForGateway') {
                continue;
            }

            for ($next = $index + 1; isset($tokens[$next]); $next++) {
                if (is_array($tokens[$next]) && $tokens[$next][0] === T_WHITESPACE) {
                    continue;
                }

                if ($tokens[$next] === '(') {
                    return true;
                }

                break;
            }
        }

        return false;
    }
}
