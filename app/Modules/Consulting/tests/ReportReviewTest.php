<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Tests;

use App\Contracts\LedgerBalanceReader;
use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Commerce\Actions\SetCommissionRate;
use App\Modules\Commerce\Filament\Pages\CommissionRatesPage;
use App\Modules\Consulting\Actions\ConsultingCheckout;
use App\Modules\Consulting\Actions\ConsultingOrderFlow;
use App\Modules\Consulting\Actions\ManageConsultingService;
use App\Modules\Consulting\Actions\ReviewConsultantProfile;
use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Modules\Consulting\Domain\ConsultingOrder;
use App\Modules\Consulting\Domain\ConsultingService;
use App\Modules\Consulting\Domain\Enums\OrderStatus;
use App\Modules\Consulting\Domain\Enums\ServiceKind;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Identity\Domain\UserProfile;
use App\Modules\Ledger\Actions\CreditWalletManually;
use App\Modules\Ledger\Domain\Wallet;
use App\Modules\Monetization\Actions\ToggleRevenueStream;
use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Reports\Domain\Enums\ReportStatus;
use App\Modules\Reports\Domain\Report;
use App\Modules\Reports\Domain\ReportDocument;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Money;
use App\Support\Reporting\ReportData;
use App\Support\Reporting\ReportMeasurement;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/**
 * بررسی گزارش توسط متخصص (بخش ۱۹-۴): فقط گزارش معتبر خود خریدار، نسخه
 * فقط‌خواندنی برای مشاور، یادداشت هر بخش و جمع‌بندی، یک پرسش تکمیلی،
 * لغو پس از مهلت تحویل (DEC-57) و کمیسیون جریان `report_review`.
 */
final class ReportReviewTest extends TestCase
{
    use RefreshDatabase;

    private const NEED = 'پیش از فرستادن گزارش به کارفرما، روش اندازه‌گیری و توصیه‌ها را بررسی کنید.';

    private const SUMMARY = 'روش اندازه‌گیری درست است ولی زمان نمونه‌برداری برای شیفت شب کافی نیست؛ یک اندازه‌گیری تکمیلی پیشنهاد می‌شود.';

    private User $consultant;

    private User $buyer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consultant = $this->consultantUser();
        $this->buyer = User::factory()->create();
    }

    public function test_the_report_page_links_to_the_reviewer_list(): void
    {
        $report = $this->report($this->buyer);
        $service = $this->service();

        $this->actingAs($this->buyer)->get(route('reports.show', $report->uuid))
            ->assertOk()
            ->assertSee(route('consulting.reviews.pick', ['report' => $report->uuid]), false);

        $this->actingAs($this->buyer)->get(route('consulting.reviews.pick', ['report' => $report->uuid]))
            ->assertOk()
            ->assertSee('data-page-help="consulting-reviews"', false)
            ->assertSee($service->title)
            ->assertSee($report->tracking_code);
    }

    public function test_a_review_service_needs_at_least_200_000_toman_and_one_per_consultant(): void
    {
        $form = [
            'kind' => 'report_review',
            'title' => 'بررسی گزارش اندازه‌گیری صدا',
            'description' => 'گزارش صادرشده شما را می‌خوانم و روی روش، نتایج و توصیه‌ها یادداشت و یک جمع‌بندی می‌نویسم.',
            'price_toman' => 150_000,
        ];

        $this->actingAs($this->consultant)->post(route('consulting.services.store'), $form)->assertSessionHasErrors('price_toman');

        $this->actingAs($this->consultant)->post(route('consulting.services.store'), [...$form, 'price_toman' => 300_000])
            ->assertRedirect(route('consulting.services.index'));
        $this->assertNull(ConsultingService::query()->sole()->duration_minutes);

        $this->actingAs($this->consultant)->post(route('consulting.services.store'), [...$form, 'price_toman' => 300_000])
            ->assertSessionHasErrors('service');
    }

    public function test_only_the_buyers_own_valid_report_can_be_sent(): void
    {
        $service = $this->service();
        $checkout = $this->app->make(ConsultingCheckout::class);

        $someoneElse = $this->report(User::factory()->create());
        $revoked = $this->report($this->buyer, ReportStatus::Revoked);

        foreach ([$someoneElse->uuid, $revoked->uuid, null] as $uuid) {
            try {
                $checkout->place($this->buyer, $service, self::NEED, [], null, false, $uuid);
                $this->fail('گزارش نامعتبر پذیرفته شد.');
            } catch (RuntimeException) {
                // انتظار می‌رفت.
            }
        }

        $this->assertSame(0, ConsultingOrder::query()->count());
    }

    public function test_the_consultant_reads_the_report_and_the_buyer_gets_notes_with_the_disclaimer(): void
    {
        $order = $this->accepted();

        $this->actingAs($this->consultant)->get(route('consulting.orders.show', $order->uuid))
            ->assertOk()
            ->assertSee('سالن پرس')
            ->assertSee('92.4 dBA')
            ->assertSee('name="notes[results]"', false)
            ->assertDontSee('name="notes[fake]"', false);

        $this->actingAs($this->consultant)->post(route('consulting.orders.review', $order->uuid), [
            'notes' => ['results' => 'نقطه ۳ بالای حد است.', 'fake' => 'کلید ساختگی'],
            'summary' => self::SUMMARY,
        ])->assertRedirect(route('consulting.orders.show', $order->uuid));

        $order = $order->fresh();
        $this->assertSame(OrderStatus::Delivered, $order?->status);
        $this->assertSame(['results' => 'نقطه ۳ بالای حد است.'], $order?->reviewNotes());

        $this->actingAs($this->buyer)->get(route('consulting.orders.show', $order?->uuid))
            ->assertSee('نقطه ۳ بالای حد است.')
            ->assertSee(self::SUMMARY)
            ->assertSee('این نظر کارشناسی است؛ تأیید رسمی گزارش یا انطباق قانونی نیست.');

        // گزارش هیچ نشانی از بررسی نمی‌گیرد.
        $this->actingAs($this->buyer)->get(route('reports.show', $order?->report_uuid))->assertDontSee('تأییدشده توسط');
    }

    public function test_one_follow_up_question_is_allowed(): void
    {
        $order = $this->delivered();
        $flow = $this->app->make(ConsultingOrderFlow::class);

        $this->actingAs($this->buyer)->post(route('consulting.orders.follow-up', $order->uuid), ['question' => 'اندازه‌گیری تکمیلی با کدام دستگاه؟'])
            ->assertRedirect(route('consulting.orders.show', $order->uuid));
        $this->assertSame(OrderStatus::FollowUp, $order->fresh()?->status);

        // تا پاسخ، آزادسازی خودکار نمی‌رسد.
        Carbon::setTestNow(Carbon::now()->addDays(8));
        $this->artisan('consulting:sweep')->assertSuccessful();
        $this->assertSame(OrderStatus::FollowUp, $order->fresh()?->status);

        $flow->answerFollowUp($order->fresh() ?? $order, $this->consultant->id, 'دوزیمتر فردی در کل شیفت.');
        $this->assertSame(OrderStatus::Delivered, $order->fresh()?->status);

        $this->expectException(RuntimeException::class);
        $flow->askFollowUp($order->fresh() ?? $order, $this->buyer->id, 'یک پرسش دیگر');
    }

    public function test_the_buyer_cancels_after_the_deadline_for_a_full_refund(): void
    {
        $order = $this->accepted();

        $this->actingAs($this->buyer)->post(route('consulting.orders.cancel', $order->uuid))->assertSessionHasErrors('order');

        Carbon::setTestNow(Carbon::now()->addDays(6));

        $this->actingAs($this->buyer)->get(route('consulting.orders.show', $order->uuid))->assertSee('لغو و بازگشت پول');
        $this->actingAs($this->buyer)->post(route('consulting.orders.cancel', $order->uuid))
            ->assertRedirect(route('consulting.orders.show', $order->uuid));

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()?->status);
        $this->assertSame(300_000, $this->wallet($this->buyer));
    }

    public function test_the_review_commission_follows_its_own_flow_rate(): void
    {
        $this->app->make(SetCommissionRate::class)->handle('report_review', 1000, Carbon::today(), $this->admin(AdminRole::Super)->id);

        $order = $this->delivered();
        $this->app->make(ConsultingOrderFlow::class)->confirm($order, $this->buyer->id);

        $this->assertSame(30_000, $order->fresh()?->commission_toman);
        $this->assertSame(270_000, $this->app->make(LedgerBalanceReader::class)->balanceOf(LedgerAccountRef::vendorPayable($this->consultant->id))->toman);
    }

    public function test_switching_the_review_stream_off_leaves_consulting_open(): void
    {
        $this->app->make(ToggleRevenueStream::class)->handle(RevenueStream::ReportReview, false);
        $checkout = $this->app->make(ConsultingCheckout::class);

        $this->assertFalse($checkout->isOpen(ServiceKind::ReportReview));
        $this->assertTrue($checkout->isOpen(ServiceKind::Online));
    }

    public function test_the_commission_page_adds_a_rate_and_keeps_history(): void
    {
        $this->actingAs($this->admin(AdminRole::Finance))->get('/'.config('admin.path').'/commission-rates')->assertForbidden();

        $this->actingAs($this->admin(AdminRole::Super));
        Livewire::test(CommissionRatesPage::class)
            ->set('percents.consulting', '12.5')
            ->call('save', 'consulting')
            ->assertSet('error', null)
            ->set('dates.shop', Carbon::yesterday()->toDateString())
            ->call('save', 'shop')
            ->assertSet('error', 'تاریخ اثر نمی‌تواند گذشته باشد؛ فروش‌های گذشته نرخ خودشان را دارند.');

        $this->assertSame(1250, $this->app->make(ConsultingCheckout::class)->rateBp());
        $this->assertTrue(DB::table('audit_logs')->where('action', 'commerce.commission_rate_set')->exists());
    }

    private function accepted(): ConsultingOrder
    {
        $service = ConsultingService::query()->first() ?? $this->service();
        $report = $this->report($this->buyer);
        app(CreditWalletManually::class)->handle($this->buyer->id, Money::toman(300_000), null, idempotencyKey: (string) Str::uuid7());

        $checkout = $this->app->make(ConsultingCheckout::class);
        $order = $checkout->payFromWallet($checkout->place($this->buyer, $service, self::NEED, [], null, false, $report->uuid));

        return $this->app->make(ConsultingOrderFlow::class)->accept($order, $this->consultant->id);
    }

    private function delivered(): ConsultingOrder
    {
        return $this->app->make(ConsultingOrderFlow::class)->submitReview($this->accepted(), $this->consultant->id, ['results' => 'نقطه ۳ بالای حد است.'], self::SUMMARY);
    }

    private function report(User $owner, ReportStatus $status = ReportStatus::Issued): Report
    {
        $document = new ReportDocument(
            title: 'اندازه‌گیری صدای سالن پرس',
            clientName: 'کارخانه نمونه',
            site: 'سالن پرس',
            measuredOn: 'مهر ۱۴۰۵',
            authorName: 'کارشناس بهداشت',
            findings: 'نقطه ۳ بالاتر از حد مجاز است.',
            recommendations: 'حفاظ شنوایی و کنترل فنی منبع.',
            includeEquipment: false,
            includeMethod: true,
            data: new ReportData('پروژه سالن پرس', [new ReportMeasurement('سالن پرس', 'نقطه ۳', null, '92.4', 'dBA')]),
        );
        $code = 'FBH-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));

        return Report::query()->forceCreate([
            'uuid' => (string) Str::uuid7(),
            'user_id' => $owner->id,
            'status' => $status,
            'source_key' => 'project',
            'source_references' => [],
            'title' => $document->title,
            'tracking_code' => $code,
            'snapshot' => $document->issued($code, '۱ مهر ۱۴۰۵', false)->toArray(),
            'issued_at' => Carbon::now(),
        ]);
    }

    private function service(): ConsultingService
    {
        $manage = $this->app->make(ManageConsultingService::class);
        $service = $manage->submit($this->consultant, [
            'kind' => ServiceKind::ReportReview,
            'title' => 'بررسی گزارش اندازه‌گیری صدا',
            'description' => 'گزارش صادرشده شما را می‌خوانم و روی روش، نتایج و توصیه‌ها یادداشت و یک جمع‌بندی می‌نویسم.',
            'duration_minutes' => null,
            'price_toman' => 300_000,
            'cities' => [],
        ]);

        return $manage->approve($service, $this->admin(AdminRole::Content)->id);
    }

    private function consultantUser(): User
    {
        $user = User::factory()->create(['name' => 'سارا احمدی']);
        UserProfile::factory()->for($user)->ofType(ProfileType::Consultant)->active()->create();

        $this->actingAs($user)->post(route('consulting.profile.update'), [
            'slug' => 'sara-ahmadi',
            'display_name' => 'سارا احمدی',
            'headline' => 'کارشناس ارشد بهداشت حرفه‌ای',
            'offerings' => ['noise'],
            'bio' => 'پانزده سال اندازه‌گیری عوامل زیان‌آور در صنایع فولاد و نساجی، ارزیابی مواجهه با صدا و گرد و غبار.',
            'province' => 'isfahan',
            'city' => 'kashan',
        ])->assertRedirect();
        $this->app->make(ReviewConsultantProfile::class)->approve(ConsultantProfile::query()->where('user_id', $user->id)->sole(), $this->admin(AdminRole::Content)->id);

        return $user->fresh() ?? $user;
    }

    private function wallet(User $user): int
    {
        return Wallet::query()->where('user_id', $user->id)->value('cached_balance_toman') ?? 0;
    }

    private function admin(AdminRole $role): User
    {
        Filament::setCurrentPanel(Filament::getPanel('fbh'));

        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, $role);

        return $user->fresh() ?? $user;
    }
}
