<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Tests;

use App\Contracts\LedgerRecorder;
use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Commerce\Domain\Enums\PayoutStatus;
use App\Modules\Commerce\Domain\PayoutRequest;
use App\Modules\Commerce\Domain\Sheba;
use App\Modules\Commerce\Domain\VendorBankAccount;
use App\Modules\Commerce\Filament\Pages\PayoutRequestsPage;
use App\Modules\Commerce\Services\Payouts;
use App\Modules\Identity\Actions\RequestProfileActivation;
use App\Modules\Identity\Actions\ReviewProfileRequest;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Workspace\Domain\UserNotification;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Ledger\LedgerEntryLine;
use App\Support\Ledger\LedgerTransactionRequest;
use App\Support\Money;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * درخواست تسویه فروشنده و مدرس (بخش ۱۸-۶، DEC-45).
 */
final class PayoutRequestTest extends TestCase
{
    use RefreshDatabase;

    private const string SHEBA = 'IR110170000000123456789001';

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('fbh'));
    }

    public function test_sheba_input_is_normalised_and_checked(): void
    {
        $this->assertSame(self::SHEBA, Sheba::fromInput('ir11 0170 0000 0012 3456 7890 01')->value);
        $this->assertSame(self::SHEBA, Sheba::fromInput('۱۱۰۱۷۰۰۰۰۰۰۰۱۲۳۴۵۶۷۸۹۰۰۱')->value);
        $this->assertSame('IR** **** 9001', Sheba::fromInput(self::SHEBA)->masked());

        $this->expectException(InvalidArgumentException::class);
        Sheba::fromInput('IR120170000000123456789001');
    }

    public function test_the_bank_account_is_stored_encrypted_and_shown_masked(): void
    {
        $vendor = $this->vendor();

        $this->actingAs($vendor)
            ->put(route('commerce.vendor.settlement.account'), ['sheba' => self::SHEBA, 'holder_name' => 'مریم رضایی'])
            ->assertRedirect(route('commerce.vendor.settlement'));

        $raw = (string) DB::table('vendor_bank_accounts')->where('user_id', $vendor->id)->value('sheba');
        $this->assertStringNotContainsString('0123456789', $raw);
        $this->assertSame(self::SHEBA, VendorBankAccount::query()->sole()->sheba()->value);

        $this->actingAs($vendor)->get(route('commerce.vendor.settlement'))
            ->assertOk()
            ->assertSee('IR** **** 9001')
            ->assertDontSee(self::SHEBA)
            ->assertSee('data-page-help="settlement"', false);

        $audit = DB::table('audit_logs')->where('action', 'commerce.bank_account_saved')->sole();
        $this->assertStringNotContainsString('9001', (string) $audit->after.(string) $audit->context);
    }

    public function test_an_invalid_sheba_is_refused_with_a_message(): void
    {
        $this->actingAs($this->vendor())
            ->put(route('commerce.vendor.settlement.account'), ['sheba' => 'IR00123', 'holder_name' => 'مریم'])
            ->assertSessionHasErrors('sheba');

        $this->assertSame(0, VendorBankAccount::query()->count());
    }

    public function test_requesting_follows_the_minimum_the_balance_and_one_open_request(): void
    {
        $vendor = $this->vendorWithAccount();
        $this->credit($vendor, 700_000);

        $this->actingAs($vendor)->post(route('commerce.vendor.settlement.request'), ['amount' => '400000'])
            ->assertSessionHasErrors('amount');
        $this->actingAs($vendor)->post(route('commerce.vendor.settlement.request'), ['amount' => '800000'])
            ->assertSessionHasErrors('amount');

        $this->actingAs($vendor)->post(route('commerce.vendor.settlement.request'), ['amount' => '۶۰۰۰۰۰'])
            ->assertRedirect(route('commerce.vendor.settlement'));

        $payout = PayoutRequest::query()->sole();
        $this->assertSame(600_000, $payout->amount_toman);
        $this->assertSame(PayoutStatus::Requested, $payout->status);
        $this->assertSame(self::SHEBA, $payout->sheba()->value);

        $this->actingAs($vendor)->post(route('commerce.vendor.settlement.request'), ['amount' => '500000'])
            ->assertSessionHasErrors('amount');
        $this->assertSame(1, PayoutRequest::query()->count());

        $this->assertSame(700_000, $this->app->make(Payouts::class)->owed($vendor->id)->toman, 'درخواست باز پولی جابه‌جا نمی‌کند.');
    }

    public function test_a_request_needs_a_bank_account_and_a_balance_over_the_minimum(): void
    {
        $vendor = $this->vendor();
        $this->credit($vendor, 900_000);

        $this->actingAs($vendor)->post(route('commerce.vendor.settlement.request'), ['amount' => '600000'])
            ->assertSessionHasErrors('amount');

        $poor = $this->vendorWithAccount();
        $this->credit($poor, 300_000);

        $this->actingAs($poor)->get(route('commerce.vendor.settlement'))
            ->assertOk()
            ->assertSee('هنوز به')
            ->assertDontSee(route('commerce.vendor.settlement.request'));
    }

    public function test_the_vendor_can_cancel_an_open_request_but_not_someone_elses(): void
    {
        $vendor = $this->vendorWithAccount();
        $this->credit($vendor, 600_000);
        $payout = $this->request($vendor, 600_000);

        $this->actingAs($this->vendorWithAccount())
            ->post(route('commerce.vendor.settlement.cancel', $payout->uuid))
            ->assertNotFound();

        $this->actingAs($vendor)->post(route('commerce.vendor.settlement.cancel', $payout->uuid))
            ->assertRedirect(route('commerce.vendor.settlement'));

        $this->assertSame(PayoutStatus::Cancelled, $payout->fresh()?->status);
    }

    public function test_marking_paid_settles_the_ledger_once_and_notifies_the_vendor(): void
    {
        $vendor = $this->vendorWithAccount();
        $this->credit($vendor, 650_000);
        $payout = $this->request($vendor, 600_000);

        Livewire::actingAs($this->admin())
            ->test(PayoutRequestsPage::class)
            ->assertSee('IR11 0170 0000 0012 3456 7890 01')
            ->call('markPaid', $payout->id)
            ->assertNotified('انجام نشد')
            ->set('references.'.$payout->id, '140509876543')
            ->call('markPaid', $payout->id)
            ->assertNotified('واریز ثبت شد')
            ->call('markPaid', $payout->id);

        $payout->refresh();
        $this->assertSame(PayoutStatus::Paid, $payout->status);
        $this->assertSame('140509876543', $payout->bank_reference);
        $this->assertSame(50_000, $this->app->make(Payouts::class)->owed($vendor->id)->toman);
        $this->assertSame(1, DB::table('ledger_transactions')->where('kind', 'commerce.vendor_settled')->count());

        $notice = UserNotification::query()->where('user_id', $vendor->id)->where('kind', 'commerce.payout_paid')->sole();
        $this->assertStringContainsString('۶۰۰٬۰۰۰', $notice->title);

        $this->assertSame(1, DB::table('audit_logs')->where('action', 'commerce.payout_paid')->count());
    }

    public function test_rejecting_needs_a_reason_and_leaves_the_balance(): void
    {
        $vendor = $this->vendorWithAccount();
        $this->credit($vendor, 600_000);
        $payout = $this->request($vendor, 600_000);

        Livewire::actingAs($this->admin())
            ->test(PayoutRequestsPage::class)
            ->call('reject', $payout->id)
            ->assertNotified('انجام نشد')
            ->set('reasons.'.$payout->id, 'نام صاحب حساب با نام شما یکی نیست.')
            ->call('reject', $payout->id)
            ->assertNotified('درخواست رد شد');

        $this->assertSame(PayoutStatus::Rejected, $payout->fresh()?->status);
        $this->assertSame(600_000, $this->app->make(Payouts::class)->owed($vendor->id)->toman);
        $this->assertSame(
            'نام صاحب حساب با نام شما یکی نیست.',
            UserNotification::query()->where('kind', 'commerce.payout_rejected')->sole()->body,
        );
    }

    public function test_a_request_larger_than_a_shrunken_balance_cannot_be_paid(): void
    {
        $vendor = $this->vendorWithAccount();
        $this->credit($vendor, 600_000);
        $payout = $this->request($vendor, 600_000);
        $this->credit($vendor, 200_000, refund: true);

        Livewire::actingAs($this->admin())
            ->test(PayoutRequestsPage::class)
            ->assertSee('مانده فعلی فقط')
            ->set('references.'.$payout->id, '123')
            ->call('markPaid', $payout->id)
            ->assertNotified('انجام نشد');

        $this->assertSame(PayoutStatus::Requested, $payout->fresh()?->status);
    }

    public function test_changing_the_account_does_not_change_an_open_request(): void
    {
        $vendor = $this->vendorWithAccount();
        $this->credit($vendor, 600_000);
        $payout = $this->request($vendor, 600_000);

        $this->actingAs($vendor)->put(route('commerce.vendor.settlement.account'), [
            'sheba' => 'IR210120000000009876543210', 'holder_name' => 'مریم رضایی',
        ]);

        $this->assertSame(self::SHEBA, $payout->fresh()?->sheba()->value);
    }

    public function test_the_panel_page_shows_help_and_counts_open_requests(): void
    {
        $vendor = $this->vendorWithAccount();
        $this->credit($vendor, 600_000);
        $this->request($vendor, 600_000);

        $this->actingAs($this->admin())
            ->get(route('filament.fbh.pages.payout-requests'))
            ->assertOk()
            ->assertSee('data-page-help="filament.fbh.pages.payout-requests"', false);

        $this->assertSame('1', PayoutRequestsPage::getNavigationBadge());
    }

    public function test_the_public_become_a_seller_page_explains_shares_and_minimum(): void
    {
        $this->get(route('commerce.sell'))
            ->assertOk()
            ->assertSee('۸۰٪')
            ->assertSee('۵۰۰٬۰۰۰ تومان')
            ->assertSee('data-page-help="sell"', false)
            ->assertSee(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('commerce.sell'))
            ->assertSee(route('identity.profiles'));

        $this->get('/')->assertSee(route('commerce.sell'));
    }

    public function test_a_user_without_a_seller_role_cannot_reach_settlement(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('commerce.vendor.settlement'))
            ->assertForbidden();
    }

    private function vendor(ProfileType $type = ProfileType::Vendor): User
    {
        $user = User::factory()->create();
        $profile = $this->app->make(RequestProfileActivation::class)->handle($user, $type);
        $this->app->make(ReviewProfileRequest::class)->approve($profile, User::factory()->create());

        return $user->fresh() ?? $user;
    }

    private function vendorWithAccount(): User
    {
        $vendor = $this->vendor(ProfileType::Instructor);
        VendorBankAccount::query()->create(['user_id' => $vendor->id, 'sheba' => self::SHEBA, 'holder_name' => 'مریم رضایی']);

        return $vendor;
    }

    private function request(User $vendor, int $toman): PayoutRequest
    {
        $this->actingAs($vendor)->post(route('commerce.vendor.settlement.request'), ['amount' => (string) $toman]);

        return PayoutRequest::query()->where('user_id', $vendor->id)->open()->sole();
    }

    private function credit(User $vendor, int $toman, bool $refund = false): void
    {
        $vendorLine = LedgerAccountRef::vendorPayable($vendor->id);
        $treasury = new LedgerAccountRef(AccountType::Treasury);

        $this->app->make(LedgerRecorder::class)->record(new LedgerTransactionRequest(
            kind: 'test.vendor_credit',
            idempotencyKey: (string) Str::uuid7(),
            entries: [
                new LedgerEntryLine($refund ? $vendorLine : $treasury, EntryDirection::Debit, Money::toman($toman)),
                new LedgerEntryLine($refund ? $treasury : $vendorLine, EntryDirection::Credit, Money::toman($toman)),
            ],
        ));
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($admin, AdminRole::Super);

        return $admin->fresh() ?? $admin;
    }
}
