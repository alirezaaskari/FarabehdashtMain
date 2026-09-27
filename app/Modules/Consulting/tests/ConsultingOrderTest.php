<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Tests;

use App\Contracts\LedgerBalanceReader;
use App\Contracts\PaymentGateway;
use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Consulting\Actions\ConsultingCheckout;
use App\Modules\Consulting\Actions\ConsultingOrderFlow;
use App\Modules\Consulting\Actions\ManageConsultingService;
use App\Modules\Consulting\Actions\PostConsultingMessage;
use App\Modules\Consulting\Actions\ReviewConsultantProfile;
use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Modules\Consulting\Domain\ConsultingOrder;
use App\Modules\Consulting\Domain\ConsultingService;
use App\Modules\Consulting\Domain\Enums\OrderStatus;
use App\Modules\Consulting\Domain\Enums\ServiceKind;
use App\Modules\Consulting\Domain\Enums\ServiceStatus;
use App\Modules\Consulting\Filament\Pages\ConsultingDisputesPage;
use App\Modules\Consulting\Filament\Pages\ConsultingServicesPage;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Identity\Domain\UserProfile;
use App\Modules\Ledger\Actions\CreditWalletManually;
use App\Modules\Ledger\Domain\Wallet;
use App\Modules\Monetization\Actions\ToggleRevenueStream;
use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Workspace\Domain\UserNotification;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Money;
use App\Support\Payments\FakeZarinPalGateway;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/**
 * فروش خدمت مشاوره (بخش ۱۹-۳): تأیید خدمت پیش از فروش، پول در امانت تا
 * پایان کار، بازگشت کامل با رد یا بی‌پاسخی (DEC-53)، آزادسازی پس از
 * تأیید یا مهلت (DEC-54) و شماره خریدار فقط با اجازه خودش (DEC-55).
 */
final class ConsultingOrderTest extends TestCase
{
    use RefreshDatabase;

    private const NEED = 'سالن پرس با ۴۰ کارگر داریم و می‌خواهیم برنامه حفاظت شنوایی را از صفر راه بیندازیم.';

    private User $consultant;

    private User $buyer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(PaymentGateway::class, new FakeZarinPalGateway);

        $this->consultant = $this->consultantUser();
        $this->buyer = User::factory()->create(['mobile' => '09121112233']);
    }

    public function test_a_new_service_waits_for_the_admin_before_it_is_sold(): void
    {
        $this->actingAs($this->consultant)->post(route('consulting.services.store'), [
            'kind' => 'online',
            'title' => 'جلسه برنامه حفاظت شنوایی',
            'description' => 'یک جلسه آنلاین برای مرور اندازه‌گیری‌ها و نوشتن برنامه حفاظت شنوایی کارگاه.',
            'duration_minutes' => 60,
            'price_toman' => 800_000,
        ])->assertRedirect(route('consulting.services.index'));

        $service = ConsultingService::query()->sole();
        $this->assertSame(ServiceStatus::Pending, $service->status);
        $this->get(route('consulting.show', 'sara-ahmadi'))->assertDontSee('جلسه برنامه حفاظت شنوایی');

        $this->actingAs($this->admin());
        Livewire::test(ConsultingServicesPage::class)->call('approve', $service->id)->assertSet('rows', []);

        $this->assertSame(ServiceStatus::Published, $service->fresh()?->status);
        $this->get(route('consulting.show', 'sara-ahmadi'))->assertSee('جلسه برنامه حفاظت شنوایی');
        $this->assertTrue(UserNotification::query()->where('user_id', $this->consultant->id)->where('kind', 'consulting.service_published')->exists());
    }

    public function test_a_rejection_needs_a_reason(): void
    {
        $service = $this->service(publish: false);

        $this->expectException(RuntimeException::class);

        $this->app->make(ManageConsultingService::class)->reject($service, $this->admin()->id, ' ');
    }

    public function test_paying_from_the_wallet_holds_the_money_in_escrow(): void
    {
        $service = $this->service();
        app(CreditWalletManually::class)->handle($this->buyer->id, Money::toman(1_000_000), null);

        $this->actingAs($this->buyer)->post(route('consulting.orders.store', $service->uuid), [
            'need' => self::NEED,
            'times' => ['شنبه ساعت ۱۰', 'یکشنبه ساعت ۱۴', ''],
            'payment' => 'wallet',
        ])->assertRedirect();

        $order = ConsultingOrder::query()->sole();
        $this->assertSame(OrderStatus::AwaitingConsultant, $order->status);
        $this->assertSame(120_000, $order->commission_toman);
        $this->assertSame(200_000, $this->wallet($this->buyer));
        $this->assertSame(800_000, $this->balance(new LedgerAccountRef(AccountType::ServiceEscrow)));
        $this->assertTrue(UserNotification::query()->where('user_id', $this->consultant->id)->where('kind', 'consulting.order_paid')->exists());
    }

    public function test_the_gateway_callback_holds_the_money_once(): void
    {
        $service = $this->service();

        $this->actingAs($this->buyer)->post(route('consulting.orders.store', $service->uuid), [
            'need' => self::NEED,
            'times' => ['شنبه ساعت ۱۰', 'یکشنبه ساعت ۱۴'],
        ])->assertRedirectContains('fake-gateway.test');

        $order = ConsultingOrder::query()->sole();
        $callback = route('consulting.orders.callback', ['Authority' => $order->gateway_authority, 'Status' => 'OK']);

        $this->get($callback)->assertRedirect(route('consulting.orders.show', $order->uuid));
        $this->get($callback)->assertRedirect(route('consulting.orders.show', $order->uuid));

        $this->assertSame(OrderStatus::AwaitingConsultant, $order->fresh()?->status);
        $this->assertSame(800_000, $this->balance(new LedgerAccountRef(AccountType::ServiceEscrow)));
    }

    public function test_declining_refunds_the_whole_price_to_the_wallet(): void
    {
        $order = $this->paid();

        $this->actingAs($this->consultant)
            ->post(route('consulting.orders.decline', $order->uuid), ['reason' => 'در این هفته وقت ندارم.'])
            ->assertRedirect(route('consulting.orders.show', $order->uuid));

        $this->assertSame(OrderStatus::Declined, $order->fresh()?->status);
        $this->assertSame(1_000_000, $this->wallet($this->buyer));
        $this->assertSame(0, $this->balance(new LedgerAccountRef(AccountType::ServiceEscrow)));
    }

    public function test_confirming_the_work_releases_the_share_minus_commission(): void
    {
        $order = $this->paid();
        $flow = $this->app->make(ConsultingOrderFlow::class);

        $flow->accept($order, $this->consultant->id, 'شنبه ساعت ۱۰', 'https://meet.example.com/abc');
        $flow->deliver($order->fresh() ?? $order, $this->consultant->id);

        $this->actingAs($this->buyer)
            ->post(route('consulting.orders.confirm', $order->uuid))
            ->assertRedirect(route('consulting.orders.show', $order->uuid));

        $this->assertSame(OrderStatus::Completed, $order->fresh()?->status);
        $this->assertSame(680_000, $this->balance(LedgerAccountRef::vendorPayable($this->consultant->id)));
        $this->assertSame(120_000, $this->balance(new LedgerAccountRef(AccountType::PlatformRevenue)));
    }

    public function test_only_the_buyer_confirms_and_only_the_consultant_accepts(): void
    {
        $order = $this->paid();

        $this->actingAs($this->buyer)
            ->post(route('consulting.orders.accept', $order->uuid), ['scheduled_for' => 'شنبه'])
            ->assertSessionHasErrors('order');

        $this->actingAs(User::factory()->create())
            ->get(route('consulting.orders.show', $order->uuid))
            ->assertNotFound();

        $this->assertSame(OrderStatus::AwaitingConsultant, $order->fresh()?->status);
    }

    public function test_a_dispute_is_split_by_the_finance_admin(): void
    {
        $order = $this->paid();
        $flow = $this->app->make(ConsultingOrderFlow::class);
        $flow->accept($order, $this->consultant->id, 'شنبه ساعت ۱۰', null);
        $flow->deliver($order->fresh() ?? $order, $this->consultant->id);

        $this->actingAs($this->buyer)
            ->post(route('consulting.orders.dispute', $order->uuid), ['reason' => 'جلسه نیمه‌کاره ماند و گزارش نرسید.'])
            ->assertRedirect(route('consulting.orders.show', $order->uuid));
        $this->assertSame(OrderStatus::Disputed, $order->fresh()?->status);

        $this->actingAs($this->adminWith(AdminRole::Content))
            ->get('/'.config('admin.path').'/consulting-disputes')
            ->assertForbidden();

        $this->actingAs($this->adminWith(AdminRole::Finance));
        Livewire::test(ConsultingDisputesPage::class)
            ->set('amounts.o'.$order->id, 400_000)
            ->set('notes.o'.$order->id, 'نیمی از کار انجام شد.')
            ->call('split', $order->id)
            ->assertSet('rows', []);

        $order = $order->fresh();
        $this->assertSame(OrderStatus::Resolved, $order?->status);
        $this->assertSame(400_000, $order?->refunded_toman);
        $this->assertSame(600_000, $this->wallet($this->buyer));
        // کمیسیون فقط از نیمه آزادشده: ۴۰۰٬۰۰۰ − ۶۰٬۰۰۰.
        $this->assertSame(340_000, $this->balance(LedgerAccountRef::vendorPayable($this->consultant->id)));
    }

    public function test_the_sweep_refunds_unanswered_requests_and_releases_quiet_deliveries(): void
    {
        $silent = $this->paid();
        $delivered = $this->paid(credit: 800_000);
        $flow = $this->app->make(ConsultingOrderFlow::class);
        $flow->accept($delivered, $this->consultant->id, 'شنبه', null);
        $flow->deliver($delivered->fresh() ?? $delivered, $this->consultant->id);

        $this->artisan('consulting:sweep')->assertSuccessful();
        $this->assertSame(OrderStatus::AwaitingConsultant, $silent->fresh()?->status);

        Carbon::setTestNow(Carbon::now()->addDays(8));
        $this->artisan('consulting:sweep')->assertSuccessful();

        $this->assertSame(OrderStatus::Expired, $silent->fresh()?->status);
        $this->assertSame(OrderStatus::Completed, $delivered->fresh()?->status);
        $this->assertSame(1_000_000, $this->wallet($this->buyer));
        $this->assertSame(680_000, $this->balance(LedgerAccountRef::vendorPayable($this->consultant->id)));
    }

    public function test_the_buyer_mobile_is_shown_only_with_permission(): void
    {
        $hidden = $this->paid();
        $shared = $this->paid(credit: 800_000, shareMobile: true);

        $this->actingAs($this->consultant)->get(route('consulting.orders.show', $hidden->uuid))->assertOk()->assertDontSee('09121112233');
        $this->actingAs($this->consultant)->get(route('consulting.orders.show', $shared->uuid))->assertOk()->assertSee('09121112233');
        $this->actingAs($this->buyer)->get(route('consulting.orders.show', $shared->uuid))->assertOk()->assertDontSee('09121112233');
    }

    public function test_messages_stay_between_the_two_parties(): void
    {
        $order = $this->paid();

        $this->actingAs($this->buyer)
            ->post(route('consulting.orders.message', $order->uuid), ['body' => 'نقشه سالن را پیوست می‌کنم.'])
            ->assertRedirect(route('consulting.orders.show', $order->uuid));

        $this->actingAs($this->consultant)->get(route('consulting.orders.show', $order->uuid))->assertSee('نقشه سالن را پیوست می‌کنم.');
        $this->assertTrue(UserNotification::query()->where('user_id', $this->consultant->id)->where('kind', 'consulting.message')->exists());

        $this->expectException(RuntimeException::class);
        $this->app->make(PostConsultingMessage::class)->handle($order, User::factory()->create()->id, 'سلام');
    }

    public function test_switching_the_stream_off_closes_new_purchases(): void
    {
        $service = $this->service();
        $this->app->make(ToggleRevenueStream::class)->handle(RevenueStream::ConsultingService, false);

        $this->assertFalse($this->app->make(ConsultingCheckout::class)->isOpen());

        $this->actingAs($this->buyer)->post(route('consulting.orders.store', $service->uuid), [
            'need' => self::NEED,
            'times' => ['شنبه ساعت ۱۰', 'یکشنبه ساعت ۱۴'],
        ])->assertSessionHasErrors('order');

        $this->assertSame(0, ConsultingOrder::query()->count());
    }

    public function test_workspace_pages_open_with_their_guides(): void
    {
        $order = $this->paid();

        $this->actingAs($this->consultant)->get(route('consulting.services.index'))->assertOk()->assertSee('data-page-help="consulting-services"', false);
        $this->actingAs($this->consultant)->get(route('consulting.orders.incoming'))->assertOk()->assertSee('data-page-help="consulting-incoming"', false);
        $this->actingAs($this->buyer)->get(route('consulting.orders.mine'))->assertOk()->assertSee($order->service->title);
        $this->actingAs($this->buyer)->get(route('consulting.orders.create', $order->service->uuid))->assertOk()->assertSee('data-page-help="consulting-order"', false);
        $this->actingAs($this->buyer)->get(route('consulting.services.index'))->assertForbidden();
    }

    private function paid(int $credit = 1_000_000, bool $shareMobile = false): ConsultingOrder
    {
        $service = ConsultingService::query()->first() ?? $this->service();
        app(CreditWalletManually::class)->handle($this->buyer->id, Money::toman($credit), null, idempotencyKey: (string) Str::uuid7());

        $checkout = $this->app->make(ConsultingCheckout::class);
        $order = $checkout->place($this->buyer, $service, self::NEED, ['شنبه ساعت ۱۰', 'یکشنبه ساعت ۱۴'], null, $shareMobile);

        return $checkout->payFromWallet($order);
    }

    private function service(bool $publish = true): ConsultingService
    {
        $service = $this->app->make(ManageConsultingService::class)->submit($this->consultant, [
            'kind' => ServiceKind::Online,
            'title' => 'جلسه برنامه حفاظت شنوایی',
            'description' => 'یک جلسه آنلاین برای مرور اندازه‌گیری‌ها و نوشتن برنامه حفاظت شنوایی کارگاه.',
            'duration_minutes' => 60,
            'price_toman' => 800_000,
            'cities' => [],
        ]);

        return $publish ? $this->app->make(ManageConsultingService::class)->approve($service, $this->admin()->id) : $service;
    }

    private function consultantUser(): User
    {
        $user = User::factory()->create(['name' => 'سارا احمدی']);
        UserProfile::factory()->for($user)->ofType(ProfileType::Consultant)->active()->create();

        $this->actingAs($user)->post(route('consulting.profile.update'), [
            'slug' => 'sara-ahmadi',
            'display_name' => 'سارا احمدی',
            'headline' => 'کارشناس ارشد بهداشت حرفه‌ای',
            'bio' => 'پانزده سال اندازه‌گیری عوامل زیان‌آور در صنایع فولاد و نساجی، ارزیابی مواجهه با صدا و گرد و غبار.',
            'province' => 'isfahan',
            'city' => 'kashan',
        ])->assertRedirect();
        $this->app->make(ReviewConsultantProfile::class)->approve(ConsultantProfile::query()->where('user_id', $user->id)->sole(), $this->admin()->id);

        return $user->fresh() ?? $user;
    }

    private function wallet(User $user): int
    {
        return Wallet::query()->where('user_id', $user->id)->value('cached_balance_toman') ?? 0;
    }

    private function balance(LedgerAccountRef $ref): int
    {
        return $this->app->make(LedgerBalanceReader::class)->balanceOf($ref)->toman;
    }

    private function admin(): User
    {
        return $this->adminWith(AdminRole::Content);
    }

    private function adminWith(AdminRole $role): User
    {
        Filament::setCurrentPanel(Filament::getPanel('fbh'));

        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, $role);

        return $user->fresh() ?? $user;
    }
}
