<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Tests;

use App\Contracts\PaymentGateway;
use App\Models\User;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Identity\Domain\UserProfile;
use App\Modules\Jobs\Actions\SavePassport;
use App\Modules\Jobs\Domain\BankRequest;
use App\Modules\Jobs\Domain\Company;
use App\Modules\Jobs\Domain\Enums\AccessSource;
use App\Modules\Jobs\Domain\Enums\BankRequestStatus;
use App\Modules\Jobs\Domain\Passport;
use App\Modules\Jobs\Domain\ResumeAccessLog;
use App\Modules\Jobs\Filament\Pages\JobPricingPage;
use App\Modules\Jobs\Services\ResumeBank;
use App\Modules\Ledger\Actions\CreditWalletManually;
use App\Modules\Ledger\Domain\LedgerTransaction;
use App\Modules\Monetization\Actions\ToggleRevenueStream;
use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Monetization\Services\Payments\FakeSubscriptionGateway;
use App\Modules\Workspace\Domain\Enums\SmsTopic;
use App\Modules\Workspace\Domain\UserNotification;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * بانک رزومه (۲۰-۵): حضور Opt-in، کارت ناشناس، درخواست تماس با پذیرش
 * کارجو، و اعتبار بسته که فقط با پذیرش خرج می‌شود (DEC-72).
 */
final class ResumeBankTest extends TestCase
{
    use JobFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(PaymentGateway::class, new FakeSubscriptionGateway);
    }

    public function test_only_members_appear_and_the_card_is_anonymous(): void
    {
        $seeker = $this->jobseeker('09121110001', 'مریم کاظمی');
        $this->actingAs($seeker)->post(route('jobs.bank.join'))->assertSessionHasErrors('bank');

        $this->fillPassport($seeker);
        $this->actingAs($seeker)->post(route('jobs.bank.join'))->assertSessionHasNoErrors();
        $this->actingAs($seeker)->get(route('jobs.bank.index'))->assertOk()
            ->assertSee('data-page-help="resume-bank"', false)
            ->assertSee('عضو بانک رزومه هستید');

        $outsider = $this->jobseeker('09121110002', 'نفر بیرون از بانک');
        $this->fillPassport($outsider);

        $employer = $this->companyOwner();
        $this->actingAs($employer)->get(route('jobs.talent.index'))->assertOk()
            ->assertSee('data-page-help="talent-search"', false)
            ->assertSee('۴ سال سابقه')
            ->assertSee('اندازه‌گیری و ارزیابی صدا')
            ->assertDontSee('مریم کاظمی')
            ->assertDontSee('09121110001')
            ->assertDontSee('کارشناس ارشد HSE')
            ->assertDontSee('نفر بیرون از بانک');

        $this->actingAs($seeker)->get(route('jobs.talent.index'))->assertForbidden();
        $this->actingAs($this->employer())->get(route('jobs.talent.index'))->assertRedirect(route('jobs.company.edit'));
    }

    public function test_a_credit_is_spent_only_when_the_jobseeker_accepts(): void
    {
        $first = $this->member('09121110003', 'مریم کاظمی');
        $second = $this->member('09121110004', 'رضا نادری');
        $third = $this->member('09121110005', 'سارا امینی');
        $employer = $this->companyOwner();

        $this->actingAs($employer)->post(route('jobs.talent.contact', $this->token($first)))->assertSessionHasErrors('bank');

        $this->app->make(CreditWalletManually::class)->handle($employer->id, Money::toman(490_000), null);
        $this->actingAs($employer)->post(route('jobs.talent.buy'), ['payment' => 'wallet'])->assertSessionHasNoErrors();
        $this->assertSame(10, $this->credits($employer));
        $this->assertSame(1, LedgerTransaction::query()->where('kind', 'jobs.resume_bank_paid')->count());

        // رد: اعتبار برمی‌گردد و دوباره نمی‌پرسد.
        $this->actingAs($employer)->post(route('jobs.talent.contact', $this->token($first)), ['note' => 'برای واحد HSE'])->assertSessionHasNoErrors();
        $this->assertSame(9, $this->credits($employer));
        $this->assertSame(SmsTopic::Jobs, SmsTopic::forKind('jobs.bank_request'));
        $request = BankRequest::query()->where('jobseeker_id', $first->id)->sole();
        $this->actingAs($first)->get(route('jobs.bank.index'))->assertSee('فولاد سپاهان')->assertSee('برای واحد HSE');
        $this->actingAs($first)->post(route('jobs.bank.decline', $request->uuid))->assertSessionHasNoErrors();
        $this->assertSame(10, $this->credits($employer));
        $this->actingAs($employer)->post(route('jobs.talent.contact', $this->token($first)))->assertSessionHasErrors('bank');

        // پذیرش: اعتبار خرج می‌شود و شماره فقط با «نمایش» می‌آید و ثبت می‌شود.
        $this->actingAs($employer)->post(route('jobs.talent.contact', $this->token($second)));
        $accepted = BankRequest::query()->where('jobseeker_id', $second->id)->sole();
        $this->actingAs($third)->post(route('jobs.bank.accept', $accepted->uuid))->assertNotFound();
        $this->actingAs($second)->post(route('jobs.bank.accept', $accepted->uuid))->assertSessionHasNoErrors();
        $this->assertSame(9, $this->credits($employer));

        $this->actingAs($employer)->get(route('jobs.talent.requests'))->assertOk()->assertSee('رضا نادری')->assertDontSee('09121110004');
        $this->actingAs($employer)->post(route('jobs.talent.reveal', $accepted->uuid))->assertRedirect(route('jobs.talent.requests'));
        $this->actingAs($employer)->get(route('jobs.talent.requests'))->assertSee('09121110004');
        $this->actingAs($employer)->post(route('jobs.talent.reveal', BankRequest::query()->where('jobseeker_id', $first->id)->sole()->uuid))->assertSessionHasErrors('bank');
        $log = ResumeAccessLog::query()->where('jobseeker_id', $second->id)->sole();
        $this->assertSame(AccessSource::ResumeBank, $log->source);
        $this->actingAs($second)->get(route('jobs.applications.index'))->assertSee('بانک رزومه');

        // بی‌پاسخی تا پایان مهلت: اعتبار برمی‌گردد و کارفرما خبر می‌گیرد.
        $this->actingAs($employer)->post(route('jobs.talent.contact', $this->token($third)));
        $this->assertSame(8, $this->credits($employer));
        Carbon::setTestNow(Carbon::now()->addDays(7)->addMinute());
        $this->artisan('jobs:bank-expire')->assertSuccessful();
        $this->assertSame(BankRequestStatus::Expired, BankRequest::query()->where('jobseeker_id', $third->id)->sole()->status);
        $this->assertSame(9, $this->credits($employer));
        $this->assertSame(3, UserNotification::query()->where('user_id', $employer->id)->where('kind', 'jobs.bank_answered')->count());
    }

    public function test_leaving_the_bank_declines_open_requests(): void
    {
        $seeker = $this->member('09121110006', 'مریم کاظمی');
        $employer = $this->companyOwner();
        $this->app->make(ToggleRevenueStream::class)->handle(RevenueStream::ResumeBankAccess, false);

        // کلید درآمد خاموش: درخواست بی‌اعتبار و بی‌خرید.
        $this->actingAs($employer)->post(route('jobs.talent.contact', $this->token($seeker)))->assertSessionHasNoErrors();
        $this->assertFalse(BankRequest::query()->sole()->charged);

        $this->actingAs($seeker)->post(route('jobs.bank.leave'))->assertSessionHasNoErrors();
        $this->assertSame(BankRequestStatus::Declined, BankRequest::query()->sole()->status);
        $this->assertFalse(Passport::of($seeker->id)->in_bank);
        $this->actingAs($employer)->get(route('jobs.talent.index'))->assertSee('۰ کارجو');
    }

    public function test_package_price_size_and_reply_window_come_from_the_panel(): void
    {
        $this->actingAs($this->admin(AdminRole::Finance));
        Livewire::test(JobPricingPage::class)
            ->set('values.bank_price', '600,000')
            ->set('values.bank_credits', '15')
            ->set('values.bank_reply_days', '3')
            ->call('save')->assertSet('error', null);

        $seeker = $this->member('09121110007', 'مریم کاظمی');
        $employer = $this->companyOwner();
        $this->actingAs($employer)->get(route('jobs.talent.index'))->assertSee('بسته ۱۵ درخواست')->assertSee('۶۰۰٬۰۰۰');

        $this->app->make(CreditWalletManually::class)->handle($employer->id, Money::toman(600_000), null);
        $this->actingAs($employer)->post(route('jobs.talent.buy'), ['payment' => 'wallet'])->assertSessionHasNoErrors();
        $this->assertSame(15, $this->credits($employer));

        $this->actingAs($employer)->post(route('jobs.talent.contact', $this->token($seeker)));
        $this->assertTrue(BankRequest::query()->sole()->expires_at->isSameDay(Carbon::now()->addDays(3)));
    }

    private function member(string $mobile, string $name): User
    {
        $user = $this->jobseeker($mobile, $name);
        $this->fillPassport($user);
        $this->actingAs($user)->post(route('jobs.bank.join'))->assertSessionHasNoErrors();
        auth()->logout();

        return $user;
    }

    private function jobseeker(string $mobile, string $name): User
    {
        $user = User::factory()->create(['mobile' => $mobile, 'name' => $name]);
        UserProfile::factory()->for($user)->ofType(ProfileType::Jobseeker)->active()->create();

        return $user->fresh() ?? $user;
    }

    private function fillPassport(User $user): void
    {
        $this->app->make(SavePassport::class)->profile($user->id, 'کارشناس ارشد HSE', 'isfahan', 'isfahan', 4, [$this->skill('noise-measurement')->id]);
    }

    private function token(User $user): string
    {
        return (string) Passport::of($user->id)->bank_token;
    }

    private function credits(User $employer): int
    {
        return $this->app->make(ResumeBank::class)->credits(Company::query()->where('user_id', $employer->id)->sole());
    }
}
