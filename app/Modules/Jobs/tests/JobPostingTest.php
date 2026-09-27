<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Tests;

use App\Contracts\PaymentGateway;
use App\Contracts\WalletStatementReader;
use App\Models\User;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Identity\Actions\DeactivateProfile;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Jobs\Domain\Company;
use App\Modules\Jobs\Domain\CompanyDocument;
use App\Modules\Jobs\Domain\Enums\PostingState;
use App\Modules\Jobs\Domain\Enums\ReviewStatus;
use App\Modules\Jobs\Domain\JobPosting;
use App\Modules\Jobs\Domain\PostingPayment;
use App\Modules\Jobs\Filament\Pages\JobPricingPage;
use App\Modules\Jobs\Filament\Pages\JobReviewPage;
use App\Modules\Jobs\Seo\JobSitemapSource;
use App\Modules\Jobs\Services\JobPricing;
use App\Modules\Ledger\Actions\CreditWalletManually;
use App\Modules\Ledger\Domain\LedgerTransaction;
use App\Modules\Monetization\Actions\ToggleRevenueStream;
use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Monetization\Services\Payments\FakeSubscriptionGateway;
use App\Modules\Workspace\Domain\UserNotification;
use App\Support\Money;
use App\Support\Seo\SitemapUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * کارفرما و آگهی شغلی (بخش ۲۰-۱): تأیید صفحه شرکت با مدرک خصوصی (DEC-65)،
 * صف تأیید آگهی، اولین آگهی رایگان و پرداخت بعدی‌ها (DEC-63) با قیمت و مدت
 * پنل، noindex و ۴۱۰ آگهی منقضی (DEC-66) و داده ساختاریافته JobPosting.
 */
final class JobPostingTest extends TestCase
{
    use JobFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(PaymentGateway::class, new FakeSubscriptionGateway);
    }

    public function test_only_an_approved_employer_builds_a_company_page_that_waits_for_the_admin(): void
    {
        $this->actingAs(User::factory()->create())->get(route('jobs.company.edit'))->assertForbidden();

        $employer = $this->employer();
        $this->actingAs($employer)->get(route('jobs.company.edit'))->assertOk()->assertSee('data-page-help="company"', false);
        $this->actingAs($employer)->post(route('jobs.company.update'), $this->companyForm())->assertRedirect(route('jobs.company.edit'));

        $company = Company::query()->sole();
        $this->assertSame(ReviewStatus::Pending, $company->status);
        $this->get(route('jobs.companies.show', 'sepahan-steel'))->assertNotFound();

        // آگهی پیش از تأیید شرکت ثبت نمی‌شود.
        $this->actingAs($employer)->get(route('jobs.employer.postings.create'))->assertRedirect(route('jobs.company.edit'));
    }

    public function test_the_company_document_is_private_to_the_employer_and_the_jobs_admin(): void
    {
        Storage::fake('local');
        $employer = $this->employer();

        $this->actingAs($employer)->post(route('jobs.documents.store'), ['document' => UploadedFile::fake()->create('rasmi.pdf', 120, 'application/pdf')])
            ->assertRedirect(route('jobs.company.edit'));
        $document = CompanyDocument::query()->sole();

        $this->actingAs($employer)->get(route('jobs.documents.download', $document->uuid))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('jobs.documents.download', $document->uuid))->assertNotFound();
        $this->actingAs($this->admin(AdminRole::Jobs))->get(route('jobs.documents.download', $document->uuid))->assertOk();
    }

    public function test_the_first_posting_is_published_free_on_approval_with_job_posting_schema(): void
    {
        $employer = $this->companyOwner();
        $noise = $this->skill('noise-measurement');

        $this->actingAs($employer)->post(route('jobs.employer.postings.store'), $this->postingForm(['skills' => [$noise->id], 'salary_min' => '۲۵٬۰۰۰٬۰۰۰', 'salary_max' => '35000000']))
            ->assertRedirect(route('jobs.employer.postings.index'));
        $posting = JobPosting::query()->sole();
        $this->assertSame(PostingState::Unpublished, $posting->state());
        $this->get(route('jobs.show', $posting->id))->assertNotFound();

        $this->actingAs($this->admin(AdminRole::Jobs));
        Livewire::test(JobReviewPage::class)->call('approvePosting', $posting->id)->assertSet('rows', []);

        $posting->refresh();
        $this->assertSame(PostingState::Live, $posting->state());
        $this->assertTrue($posting->expires_at?->isSameDay(Carbon::now()->addDays(30)));
        $this->assertSame(25_000_000, $posting->salary_min_toman);
        $this->assertTrue(PostingPayment::query()->sole()->is_free);
        $this->assertSame(0, LedgerTransaction::query()->count());
        $this->assertSame(1, UserNotification::query()->where('user_id', $employer->id)->where('kind', 'jobs.posting_approved')->count());

        auth()->logout();
        $this->get(route('jobs.show', $posting->id))->assertOk()
            ->assertSee('"@type":"JobPosting"', false)
            ->assertSee('"employmentType":"FULL_TIME"', false)
            ->assertSee('"minValue":25000000', false)
            ->assertSee('فولاد سپاهان')
            ->assertDontSee('noindex', false);
        $this->get(route('jobs.index'))->assertOk()->assertSee('کارشناس بهداشت حرفه‌ای کارخانه')->assertSee('data-page-help="jobs"', false);
        $this->get(route('jobs.companies.show', 'sepahan-steel'))->assertOk()->assertSee('کارشناس بهداشت حرفه‌ای کارخانه');
    }

    public function test_the_next_posting_waits_for_payment_at_the_panel_price(): void
    {
        $employer = $this->companyOwner();
        $this->publish($employer, 'آگهی نخست کارشناس ایمنی');

        // مدیر قیمت و مدت را از پنل عوض می‌کند.
        $this->actingAs($this->admin(AdminRole::Finance));
        Livewire::test(JobPricingPage::class)->set('price', '400,000')->set('days', '45')->call('save')->assertSet('error', null);
        $this->assertSame(400_000, $this->app->make(JobPricing::class)->price()->toman);

        $posting = $this->submitAndApprove($employer, 'کارشناس HSE کارگاه ساختمانی');
        $this->assertSame(PostingState::AwaitingPayment, $posting->state());

        $this->app->make(CreditWalletManually::class)->handle($employer->id, Money::toman(1_000_000), null);
        $this->actingAs($employer)->get(route('jobs.employer.postings.checkout', $posting->uuid))->assertOk()->assertSee('۴۰۰٬۰۰۰');
        $this->actingAs($employer)->post(route('jobs.employer.postings.pay', $posting->uuid), ['payment' => 'wallet'])
            ->assertOk()->assertViewIs('jobs::workspace.published');

        $posting->refresh();
        $this->assertSame(PostingState::Live, $posting->state());
        $this->assertTrue($posting->expires_at?->isSameDay(Carbon::now()->addDays(45)));
        $this->assertSame(600_000, $this->app->make(WalletStatementReader::class)->balanceOf($employer->id)?->toman);
        $this->assertSame(1, LedgerTransaction::query()->where('kind', 'jobs.posting_paid')->count());
    }

    public function test_paying_through_the_gateway_and_renewing_extends_from_the_current_end(): void
    {
        $employer = $this->companyOwner();
        $this->publish($employer, 'آگهی نخست کارشناس ایمنی');
        $posting = $this->submitAndApprove($employer, 'کارشناس بهداشت محیط');

        $this->actingAs($employer)->post(route('jobs.employer.postings.pay', $posting->uuid))->assertRedirect();
        $payment = PostingPayment::query()->where('posting_id', $posting->id)->sole();
        $this->get(route('jobs.employer.postings.callback', ['Authority' => $payment->gateway_authority, 'Status' => 'OK']))->assertOk();
        $firstEnd = $posting->refresh()->expires_at;

        $this->actingAs($employer)->post(route('jobs.employer.postings.pay', $posting->uuid))->assertRedirect();
        $renewal = PostingPayment::query()->where('posting_id', $posting->id)->latest('id')->firstOrFail();
        $this->get(route('jobs.employer.postings.callback', ['Authority' => $renewal->gateway_authority, 'Status' => 'OK']))->assertOk();

        $this->assertTrue($posting->refresh()->expires_at?->equalTo($firstEnd?->copy()->addDays(30)));
        $this->assertSame(2, LedgerTransaction::query()->where('kind', 'jobs.posting_paid')->count());
    }

    public function test_with_the_revenue_switch_off_every_approved_posting_is_free(): void
    {
        $this->app->make(ToggleRevenueStream::class)->handle(RevenueStream::JobPosting, false);
        $employer = $this->companyOwner();
        $this->publish($employer, 'آگهی نخست کارشناس ایمنی');

        $posting = $this->submitAndApprove($employer, 'کارشناس ارگونومی');

        $this->assertSame(PostingState::Live, $posting->state());
    }

    public function test_an_edit_to_a_live_posting_waits_while_the_old_text_stays(): void
    {
        $employer = $this->companyOwner();
        $posting = $this->publish($employer, 'کارشناس بهداشت حرفه‌ای کارخانه');

        $this->actingAs($employer)->put(route('jobs.employer.postings.update', $posting->uuid), $this->postingForm(['title' => 'عنوان تازه آگهی ایمنی']));

        $this->assertSame(ReviewStatus::Pending, $posting->refresh()->status);
        $this->get(route('jobs.show', $posting->id))->assertSee('کارشناس بهداشت حرفه‌ای کارخانه')->assertDontSee('عنوان تازه آگهی ایمنی');
    }

    public function test_an_expired_posting_is_noindexed_then_gone(): void
    {
        $employer = $this->companyOwner();
        $posting = $this->publish($employer, 'کارشناس بهداشت حرفه‌ای کارخانه');

        $this->travel(31)->days();
        $this->get(route('jobs.show', $posting->id))->assertOk()->assertSee('noindex', false)->assertSee('مهلت این آگهی تمام شده است')
            ->assertDontSee('"@type":"JobPosting"', false);
        $this->get(route('jobs.index'))->assertDontSee('کارشناس بهداشت حرفه‌ای کارخانه');

        $this->travel(91)->days();
        $this->get(route('jobs.show', $posting->id))->assertStatus(410);
    }

    public function test_city_and_skill_pages_list_postings_and_hide_thin_pages_from_search(): void
    {
        $employer = $this->companyOwner();
        $noise = $this->skill('noise-measurement');
        $this->publish($employer, 'کارشناس اندازه‌گیری صدا', ['skills' => [$noise->id]]);

        $this->get(route('jobs.city', 'isfahan'))->assertOk()->assertSee('کارشناس اندازه‌گیری صدا')->assertSee('noindex', false);
        $this->get(route('jobs.skill', 'noise-measurement'))->assertOk()->assertSee('کارشناس اندازه‌گیری صدا')->assertSee('noindex', false);
        $this->get(route('jobs.index', ['city' => 'isfahan']))->assertRedirect(route('jobs.city', 'isfahan'));
        $this->get(route('jobs.city', 'atlantis'))->assertNotFound();

        $this->publish($employer, 'کارشناس ارشد صدا و ارتعاش', ['skills' => [$noise->id]]);
        $this->get(route('jobs.city', 'isfahan'))->assertDontSee('noindex', false);

        $urls = array_map(static fn (SitemapUrl $url): string => $url->loc, iterator_to_array($this->app->make(JobSitemapSource::class)->sitemapUrls(), false));
        $this->assertContains(route('jobs.city', 'isfahan'), $urls);
        $this->assertContains(route('jobs.skill', 'noise-measurement'), $urls);
        $this->assertContains(route('jobs.companies.show', 'sepahan-steel'), $urls);
    }

    public function test_deactivating_the_employer_role_hides_the_company_and_its_postings(): void
    {
        $employer = $this->companyOwner();
        $posting = $this->publish($employer, 'کارشناس بهداشت حرفه‌ای کارخانه');

        $this->app->make(DeactivateProfile::class)->handle($employer, ProfileType::Employer);

        $this->get(route('jobs.show', $posting->id))->assertNotFound();
        $this->get(route('jobs.companies.show', 'sepahan-steel'))->assertNotFound();
        $this->get(route('jobs.index'))->assertDontSee('کارشناس بهداشت حرفه‌ای کارخانه');
    }

    public function test_the_employer_closes_a_filled_posting(): void
    {
        $employer = $this->companyOwner();
        $posting = $this->publish($employer, 'کارشناس بهداشت حرفه‌ای کارخانه');
        $other = $this->employer();

        $this->actingAs($other)->post(route('jobs.employer.postings.close', $posting->uuid))->assertNotFound();
        $this->actingAs($employer)->post(route('jobs.employer.postings.close', $posting->uuid))->assertRedirect(route('jobs.employer.postings.index'));

        $this->assertSame(PostingState::Closed, $posting->refresh()->state());
        $this->get(route('jobs.show', $posting->id))->assertOk()->assertSee('این آگهی بسته شده است');
        $this->actingAs($employer)->get(route('jobs.employer.postings.index'))->assertOk()
            ->assertSee('بسته‌شده')->assertSee('data-page-help="employer-postings"', false);
    }
}
