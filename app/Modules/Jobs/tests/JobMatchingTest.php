<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Tests;

use App\Contracts\PaymentGateway;
use App\Models\User;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Identity\Domain\UserProfile;
use App\Modules\Jobs\Actions\SavePassport;
use App\Modules\Jobs\Domain\JobAlert;
use App\Modules\Jobs\Domain\JobAlertMatch;
use App\Modules\Jobs\Services\JobPricing;
use App\Modules\Ledger\Actions\CreditWalletManually;
use App\Modules\Monetization\Services\Payments\FakeSubscriptionGateway;
use App\Modules\Workspace\Domain\Enums\SmsTopic;
use App\Modules\Workspace\Domain\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * تطبیق مهارت و هشدار شغل (۲۰-۴): «N از M مهارت» فقط برای خود کارجو،
 * فهرست به ترتیب تطبیق، اعلان آگهی تازه یک بار برای هر کاربر، و خلاصه
 * پیامکی روزانه فقط برای کسی که روشنش کرده (DEC-73).
 */
final class JobMatchingTest extends TestCase
{
    use JobFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(PaymentGateway::class, new FakeSubscriptionGateway);
    }

    public function test_the_posting_shows_the_match_only_to_the_signed_in_jobseeker(): void
    {
        $employer = $this->companyOwner();
        $posting = $this->publish($employer, 'کارشناس بهداشت حرفه‌ای', ['skills' => [$this->skill('noise-measurement')->id, $this->skill('chemical-sampling')->id]]);
        $seeker = $this->jobseekerWith(['noise-measurement']);

        $this->actingAs($seeker)->get(route('jobs.show', $posting->id))->assertOk()
            ->assertSee('شما ۱ از ۲ مهارت این آگهی را')
            ->assertSee('ابزار', false)
            ->assertSee(route('tools.show', 'twa-ppm'), false);

        $this->actingAs($employer)->get(route('jobs.show', $posting->id))->assertOk()->assertSee('شما ۰ از ۲ مهارت');
        auth()->logout();
        $this->get(route('jobs.show', $posting->id))->assertOk()->assertDontSee('data-skill-match', false);
    }

    public function test_jobs_for_me_are_ordered_by_matching_skills(): void
    {
        $employer = $this->companyOwner();
        $this->publish($employer, 'آگهی یک مهارت', ['skills' => [$this->skill('noise-measurement')->id]]);
        $this->publish($employer, 'آگهی دو مهارت', ['skills' => [$this->skill('noise-measurement')->id, $this->skill('heat-stress')->id]]);
        $this->publish($employer, 'آگهی بی‌ربط', ['skills' => [$this->skill('fire-safety')->id]]);
        $seeker = $this->jobseekerWith(['noise-measurement', 'heat-stress']);

        $this->actingAs($seeker)->get(route('jobs.alerts.index'))->assertOk()
            ->assertSee('data-page-help="job-alerts"', false)
            ->assertSeeInOrder(['آگهی دو مهارت', '۲ از ۲ مهارت', 'آگهی یک مهارت', '۱ از ۱ مهارت'])
            ->assertDontSee('آگهی بی‌ربط');
    }

    public function test_a_new_posting_alerts_each_matching_user_once_and_renewals_stay_quiet(): void
    {
        $employer = $this->companyOwner();
        $byPassport = $this->jobseekerWith(['noise-measurement']);
        $byCity = $this->jobseekerWith([]);
        $elsewhere = $this->jobseekerWith([]);

        $this->actingAs($byPassport)->post(route('jobs.alerts.store'), ['match_passport' => '1'])->assertSessionHasNoErrors();
        $this->actingAs($byPassport)->post(route('jobs.alerts.store'), ['city' => 'isfahan'])->assertSessionHasNoErrors();
        $this->actingAs($byCity)->post(route('jobs.alerts.store'), ['city' => 'isfahan', 'employment_type' => 'full_time', 'sms' => '1']);
        $this->actingAs($elsewhere)->post(route('jobs.alerts.store'), ['city' => 'tabriz']);
        $this->actingAs($elsewhere)->post(route('jobs.alerts.store'), [])->assertSessionHasErrors('alert');

        $posting = $this->publish($employer, 'کارشناس صدا', ['skills' => [$this->skill('noise-measurement')->id]]);

        $notified = UserNotification::query()->where('kind', 'jobs.alert_match')->pluck('user_id')->sort()->values()->all();
        $this->assertSame([$byPassport->id, $byCity->id], $notified);

        // تمدید آگهی هشدار تازه نمی‌سازد.
        $this->actingAs($employer)->post(route('jobs.employer.postings.pay', $posting->uuid), ['payment' => 'wallet']);
        $this->app->make(CreditWalletManually::class)->handle($employer->id, $this->app->make(JobPricing::class)->price(), null);
        $this->actingAs($employer)->post(route('jobs.employer.postings.pay', $posting->uuid), ['payment' => 'wallet'])->assertOk();
        $this->assertSame(2, UserNotification::query()->where('kind', 'jobs.alert_match')->count());

        // خلاصه روزانه فقط برای کسی که پیامک را روشن کرده.
        $this->artisan('jobs:alert-digest')->assertSuccessful();
        $digests = UserNotification::query()->where('kind', 'jobs.alert_digest')->get();
        $this->assertSame([$byCity->id], $digests->pluck('user_id')->all());
        $this->assertStringStartsWith('1 آگهی', $digests->first()->title);
        $this->assertSame(SmsTopic::Jobs, SmsTopic::forKind('jobs.alert_digest'));
        $this->assertSame(0, JobAlertMatch::query()->whereNull('digested_at')->count());

        $this->artisan('jobs:alert-digest')->assertSuccessful();
        $this->assertSame(1, UserNotification::query()->where('kind', 'jobs.alert_digest')->count());

        $alert = JobAlert::query()->where('user_id', $byCity->id)->sole();
        $this->actingAs($elsewhere)->delete(route('jobs.alerts.destroy', $alert->id))->assertNotFound();
        $this->actingAs($byCity)->delete(route('jobs.alerts.destroy', $alert->id))->assertRedirect();
        $this->assertSame(0, JobAlert::query()->where('user_id', $byCity->id)->count());
    }

    /** @param  list<string>  $skills */
    private function jobseekerWith(array $skills): User
    {
        $user = User::factory()->create();
        UserProfile::factory()->for($user)->ofType(ProfileType::Jobseeker)->active()->create();
        $this->app->make(SavePassport::class)->profile($user->id, null, null, null, null, array_map(fn (string $slug): int => $this->skill($slug)->id, $skills));

        return $user->fresh() ?? $user;
    }
}
