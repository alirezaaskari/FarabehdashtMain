<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Core\Domain\TaxonomyTerm;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Identity\Domain\UserProfile;
use App\Modules\Jobs\Domain\Company;
use App\Modules\Jobs\Domain\Enums\PostingState;
use App\Modules\Jobs\Domain\JobPosting;
use App\Modules\Jobs\Filament\Pages\JobReviewPage;
use App\Modules\Jobs\Services\JobPricing;
use App\Modules\Ledger\Actions\CreditWalletManually;
use Filament\Facades\Filament;
use Livewire\Livewire;

/**
 * کارفرمای تأییدشده، صفحه شرکت و آگهی زنده برای تست‌های کاریابی.
 */
trait JobFixtures
{
    private const ABOUT = 'تولیدکننده ورق فولادی با دو هزار نیرو و واحد HSE مستقل در سه شیفت کاری.';

    private const DESCRIPTION = 'پایش عوامل زیان‌آور فیزیکی و شیمیایی سالن‌های تولید، تهیه برنامه حفاظت شنوایی، آموزش ایمنی کارکنان جدید و همکاری با طب کار در معاینات دوره‌ای. کار در شیفت صبح است.';

    /** @param  array<string, mixed>  $overrides */
    private function publish(User $employer, string $title, array $overrides = []): JobPosting
    {
        $posting = $this->submitAndApprove($employer, $title, $overrides);

        if ($posting->state() === PostingState::AwaitingPayment) {
            $this->app->make(CreditWalletManually::class)->handle($employer->id, $this->app->make(JobPricing::class)->price(), null);
            $this->actingAs($employer)->post(route('jobs.employer.postings.pay', $posting->uuid), ['payment' => 'wallet'])->assertOk();
            auth()->logout();
        }

        $this->assertSame(PostingState::Live, $posting->refresh()->state());

        return $posting;
    }

    /** @param  array<string, mixed>  $overrides */
    private function submitAndApprove(User $employer, string $title, array $overrides = []): JobPosting
    {
        $this->actingAs($employer)->post(route('jobs.employer.postings.store'), $this->postingForm(['title' => $title, ...$overrides]))->assertSessionHasNoErrors();
        $posting = JobPosting::query()->latest('id')->firstOrFail();

        $this->actingAs($this->admin(AdminRole::Jobs));
        Livewire::test(JobReviewPage::class)->call('approvePosting', $posting->id);
        auth()->logout();

        return $posting->refresh();
    }

    private function companyOwner(): User
    {
        $employer = $this->employer();
        $this->actingAs($employer)->post(route('jobs.company.update'), $this->companyForm())->assertSessionHasNoErrors();

        $this->actingAs($this->admin(AdminRole::Jobs));
        Livewire::test(JobReviewPage::class)->call('approveCompany', Company::query()->where('user_id', $employer->id)->sole()->id);
        auth()->logout();

        return $employer;
    }

    private function employer(): User
    {
        $user = User::factory()->create();
        UserProfile::factory()->for($user)->ofType(ProfileType::Employer)->active()->create();

        return $user->fresh() ?? $user;
    }

    private function skill(string $slug): TaxonomyTerm
    {
        return TaxonomyTerm::query()->where('taxonomy', 'job_skill')->where('slug', $slug)->sole();
    }

    /** @return array<string, mixed> */
    private function companyForm(): array
    {
        return [
            'slug' => 'sepahan-steel',
            'name' => 'فولاد سپاهان',
            'industry' => 'فولاد',
            'size' => '1000+',
            'province' => 'isfahan',
            'city' => 'isfahan',
            'about' => self::ABOUT,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function postingForm(array $overrides = []): array
    {
        return [
            'title' => 'کارشناس بهداشت حرفه‌ای کارخانه',
            'province' => 'isfahan',
            'city' => 'isfahan',
            'employment_type' => 'full_time',
            'min_experience_years' => 2,
            'description' => self::DESCRIPTION,
            'skills' => [$this->skill('chemical-sampling')->id],
            ...$overrides,
        ];
    }

    private function admin(AdminRole $role): User
    {
        Filament::setCurrentPanel(Filament::getPanel('fbh'));

        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, $role);

        return $user->fresh() ?? $user;
    }
}
