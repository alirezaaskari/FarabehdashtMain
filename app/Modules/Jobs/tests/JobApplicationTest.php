<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Tests;

use App\Contracts\PaymentGateway;
use App\Models\User;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Core\Domain\AuditLog;
use App\Modules\Identity\Domain\Enums\ProfileStatus;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Identity\Domain\UserProfile;
use App\Modules\Jobs\Domain\Enums\AccessKind;
use App\Modules\Jobs\Domain\Enums\ApplicationStatus;
use App\Modules\Jobs\Domain\JobApplication;
use App\Modules\Jobs\Domain\JobPosting;
use App\Modules\Jobs\Domain\ResumeAccessLog;
use App\Modules\Jobs\Filament\Pages\JobPricingPage;
use App\Modules\Jobs\Services\JobPricing;
use App\Modules\Monetization\Services\Payments\FakeSubscriptionGateway;
use App\Modules\Workspace\Domain\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * درخواست کارجو و صندوق کارفرما (بخش ۲۰-۲): نقش کارجوی بی‌صف (DEC-64)،
 * شماره فقط با تیک همان درخواست و ثبت هر دیده‌شدن (DEC-68)، سقف روزانه
 * پنل (DEC-74)، وضعیت‌ها با اعلان و گفت‌وگوی دوطرفه.
 */
final class JobApplicationTest extends TestCase
{
    use JobFixtures;
    use RefreshDatabase;

    private const COVER = 'سه سال اندازه‌گیری صدا و گرد و غبار در صنعت فولاد، آماده شروع از ماه آینده.';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(PaymentGateway::class, new FakeSubscriptionGateway);
        Storage::fake('local');
    }

    public function test_the_jobseeker_role_turns_on_at_once_and_old_requests_are_approved(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('identity.profiles.activate', 'jobseeker'))->assertSessionHas('status', 'نقش «کارجو» فعال شد.');

        $this->assertSame(ProfileStatus::Active, UserProfile::query()->where('user_id', $user->id)->sole()->status);
        $this->assertTrue(($user->fresh() ?? $user)->can('jobs.applications.manage'));

        // نقش‌های دیگر همچنان در صف مدیر می‌مانند.
        $this->actingAs($user)->post(route('identity.profiles.activate', 'employer'));
        $this->assertSame(ProfileStatus::Pending, UserProfile::query()->where('user_id', $user->id)->where('type', 'employer')->sole()->status);

        $waiting = UserProfile::factory()->for(User::factory()->create())->ofType(ProfileType::Jobseeker)->create(['status' => ProfileStatus::Pending->value]);
        $migration = require base_path('app/Modules/Identity/database/migrations/2026_12_21_100000_activate_pending_jobseekers.php');
        $migration->up();
        $this->assertSame(ProfileStatus::Active, $waiting->refresh()->status);
    }

    public function test_a_jobseeker_applies_free_and_the_employer_sees_contact_only_with_consent(): void
    {
        $employer = $this->companyOwner();
        $posting = $this->publish($employer, 'کارشناس بهداشت حرفه‌ای');
        $seeker = $this->jobseeker();

        $this->get(route('jobs.show', $posting->id))->assertSee(route('jobs.apply.create', $posting->id), false);
        $this->actingAs($seeker)->get(route('jobs.apply.create', $posting->id))->assertOk()->assertSee('data-page-help="apply-form"', false);

        $this->apply($seeker, $posting)->assertRedirect();
        $application = JobApplication::query()->sole();
        $this->assertSame(ApplicationStatus::Received, $application->status);
        $this->assertFalse($application->share_contact);
        Storage::disk('local')->assertExists((string) $application->resume_path);
        $this->assertSame(1, UserNotification::query()->where('user_id', $employer->id)->where('kind', 'jobs.application_received')->count());
        $this->assertTrue(AuditLog::query()->where('action', 'jobs.application_submitted')->exists());

        // باز کردن درخواست «دیده‌شده» می‌کند؛ شماره بدون اجازه دیده نمی‌شود.
        $this->actingAs($employer)->get(route('jobs.employer.applicants.show', $application->uuid))->assertOk()
            ->assertSee(self::COVER)->assertDontSee($seeker->mobile)->assertSee('اجازه نمایش شماره و ایمیلش را نداده');
        $this->assertSame(ApplicationStatus::Seen, $application->refresh()->status);
        $this->assertSame(1, UserNotification::query()->where('user_id', $seeker->id)->where('kind', 'jobs.application_status')->count());
        $this->actingAs($employer)->post(route('jobs.employer.applicants.contact', $application->uuid))->assertSessionHasErrors('application');
        $this->assertSame(0, ResumeAccessLog::query()->count());

        // کارجو اجازه می‌دهد؛ هر دیدن شماره و رزومه ثبت می‌شود.
        $this->actingAs($seeker)->post(route('jobs.applications.consent', $application->uuid), ['share_contact' => '1'])->assertRedirect();
        $this->actingAs($employer)->post(route('jobs.employer.applicants.contact', $application->uuid))
            ->assertRedirect(route('jobs.employer.applicants.show', $application->uuid));
        $this->actingAs($employer)->get(route('jobs.employer.applicants.show', $application->uuid))->assertSee($seeker->mobile);
        $this->actingAs($employer)->get(route('jobs.employer.applicants.resume', $application->uuid))->assertOk();

        $this->assertSame([AccessKind::Contact, AccessKind::Resume], ResumeAccessLog::query()->orderBy('id')->get()->map(fn (ResumeAccessLog $log) => $log->kind)->all());
        $this->actingAs($seeker)->get(route('jobs.applications.index'))->assertOk()->assertSee('فولاد سپاهان · شماره و ایمیل');
    }

    public function test_statuses_notify_and_both_sides_talk_while_others_stay_out(): void
    {
        $employer = $this->companyOwner();
        $posting = $this->publish($employer, 'کارشناس HSE');
        $seeker = $this->jobseeker();
        $this->apply($seeker, $posting);
        $application = JobApplication::query()->sole();

        $this->actingAs($employer)->post(route('jobs.employer.applicants.status', $application->uuid), ['status' => 'shortlisted'])->assertRedirect();
        $this->assertSame(ApplicationStatus::Shortlisted, $application->refresh()->status);
        $this->actingAs($employer)->post(route('jobs.employer.applicants.status', $application->uuid), ['status' => 'received'])->assertSessionHasErrors('application');

        $this->actingAs($employer)->post(route('jobs.employer.applicants.message', $application->uuid), ['body' => 'شنبه ساعت ده برای مصاحبه می‌آیید؟'])->assertRedirect();
        $this->actingAs($seeker)->post(route('jobs.applications.message', $application->uuid), ['body' => 'بله، حتماً.'])->assertRedirect();
        $this->assertSame(1, UserNotification::query()->where('user_id', $seeker->id)->where('kind', 'jobs.application_message')->count());
        $this->assertSame(1, UserNotification::query()->where('user_id', $employer->id)->where('kind', 'jobs.application_message')->count());
        $this->actingAs($seeker)->get(route('jobs.applications.show', $application->uuid))->assertOk()->assertSee('شنبه ساعت ده')->assertSee('در فهرست کوتاه');

        // کارفرمای دیگر و کاربر دیگر راهی به این درخواست ندارند.
        $other = $this->employer();
        $this->actingAs($other)->get(route('jobs.employer.applicants.show', $application->uuid))->assertNotFound();
        $this->actingAs($other)->get(route('jobs.employer.applicants.index', $posting->uuid))->assertNotFound();
        $this->actingAs($this->jobseeker())->get(route('jobs.applications.show', $application->uuid))->assertNotFound();

        $this->actingAs($employer)->post(route('jobs.employer.applicants.status', $application->uuid), ['status' => 'hired']);
        $this->actingAs($seeker)->post(route('jobs.applications.withdraw', $application->uuid))->assertSessionHasErrors('application');
        $this->actingAs($seeker)->post(route('jobs.applications.message', $application->uuid), ['body' => 'ممنون!'])->assertRedirect();
    }

    public function test_withdrawing_deletes_the_resume_and_closes_the_employer_view(): void
    {
        $employer = $this->companyOwner();
        $posting = $this->publish($employer, 'کارشناس ایمنی');
        $seeker = $this->jobseeker();
        $this->apply($seeker, $posting, share: true);
        $application = JobApplication::query()->sole();
        $path = (string) $application->resume_path;

        $this->actingAs($seeker)->post(route('jobs.applications.withdraw', $application->uuid))->assertRedirect(route('jobs.applications.index'));

        $application->refresh();
        $this->assertSame(ApplicationStatus::Withdrawn, $application->status);
        $this->assertFalse($application->share_contact);
        Storage::disk('local')->assertMissing($path);
        $this->assertSame(1, UserNotification::query()->where('user_id', $employer->id)->where('kind', 'jobs.application_withdrawn')->count());

        $this->actingAs($employer)->get(route('jobs.employer.applicants.show', $application->uuid))->assertNotFound();
        $this->actingAs($employer)->get(route('jobs.employer.applicants.resume', $application->uuid))->assertNotFound();
        $this->actingAs($employer)->get(route('jobs.employer.applicants.index', $posting->uuid))->assertOk()
            ->assertSee('درخواستی که کارجو پس گرفت')->assertDontSee((string) $seeker->name);
    }

    public function test_guards_one_application_per_posting_no_own_posting_and_the_panel_daily_limit(): void
    {
        $employer = $this->companyOwner();
        $first = $this->publish($employer, 'کارشناس یک');
        $second = $this->publish($employer, 'کارشناس دو');
        $seeker = $this->jobseeker();

        // بی‌نقش: راه فعال‌کردن کارجو را می‌بیند، نه فرم را.
        $this->actingAs(User::factory()->create())->get(route('jobs.apply.create', $first->id))->assertOk()->assertSee('اول نقش کارجو را فعال کنید');
        $this->apply($employer, $first)->assertSessionHasErrors('application');

        $this->actingAs($this->admin(AdminRole::Finance));
        Livewire::test(JobPricingPage::class)->set('values.apply_limit', '1')->call('save')->assertSet('error', null);
        $this->assertSame(1, $this->app->make(JobPricing::class)->applicationsPerDay());
        auth()->logout();

        $this->apply($seeker, $first)->assertSessionHasNoErrors();
        $this->actingAs($seeker)->get(route('jobs.apply.create', $first->id))->assertRedirect();
        $this->apply($seeker, $second)->assertSessionHasErrors('application');
        $this->assertSame(1, JobApplication::query()->count());

        // آگهی بسته‌شده درخواست نمی‌گیرد.
        $this->actingAs($employer)->post(route('jobs.employer.postings.close', $second->uuid));
        $this->actingAs($seeker)->get(route('jobs.apply.create', $second->id))->assertNotFound();
    }

    private function apply(User $user, JobPosting $posting, bool $share = false): TestResponse
    {
        return $this->actingAs($user)->post(route('jobs.apply.store', $posting->id), [
            'cover_note' => self::COVER,
            'resume' => UploadedFile::fake()->create('resume.pdf', 200, 'application/pdf'),
            'share_contact' => $share ? '1' : null,
        ]);
    }

    private function jobseeker(): User
    {
        $user = User::factory()->create();
        UserProfile::factory()->for($user)->ofType(ProfileType::Jobseeker)->active()->create();

        return $user->fresh() ?? $user;
    }
}
