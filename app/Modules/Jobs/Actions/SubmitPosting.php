<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Actions;

use App\Models\User;
use App\Modules\Jobs\Domain\Company;
use App\Modules\Jobs\Domain\Enums\PostingState;
use App\Modules\Jobs\Domain\Enums\ReviewStatus;
use App\Modules\Jobs\Domain\JobPosting;
use App\Modules\Jobs\Domain\PostingDraft;
use App\Modules\Jobs\Events\PostingSubmitted;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * کارفرما آگهی تازه یا ویرایش آگهی را برای تأیید مدیر می‌فرستد.
 *
 * هر نسخه پیش از دیده‌شدن از صف مدیر می‌گذرد؛ آگهی منتشرشده تا تأیید
 * ویرایش با متن قبلی می‌ماند. آگهی فقط زیر شرکتی که یک بار تأیید شده ثبت
 * می‌شود.
 */
final readonly class SubmitPosting
{
    public function __construct(
        private Repository $config,
        private Dispatcher $events,
    ) {}

    public function create(User $user, PostingDraft $draft): JobPosting
    {
        $company = $this->company($user);
        $max = (int) $this->config->get('jobs.limits.open_postings_max', 20);

        if ($this->openCount($company) >= $max) {
            throw new RuntimeException('بیش از '.$max.' آگهی باز همزمان نمی‌شود داشت؛ آگهی‌ای را که پر شده ببندید.');
        }

        $posting = JobPosting::query()->create([
            'uuid' => (string) Str::uuid7(),
            'company_id' => $company->id,
            'status' => ReviewStatus::Draft,
        ]);

        return $this->submit($user, $posting, $draft);
    }

    public function update(User $user, JobPosting $posting, PostingDraft $draft): JobPosting
    {
        $company = $this->company($user);

        if ($posting->company_id !== $company->id) {
            throw new RuntimeException('این آگهی مال شما نیست.');
        }

        if ($posting->state() === PostingState::Closed) {
            throw new RuntimeException('آگهی بسته‌شده ویرایش نمی‌شود؛ آگهی تازه بسازید.');
        }

        return $this->submit($user, $posting, $draft);
    }

    private function submit(User $user, JobPosting $posting, PostingDraft $draft): JobPosting
    {
        $posting->forceFill([
            'pending' => $draft->toArray(),
            'status' => ReviewStatus::Pending,
            'submitted_at' => Carbon::now(),
            'review_note' => null,
        ])->save();

        $this->events->dispatch(new PostingSubmitted($posting, (int) $user->getKey()));

        return $posting;
    }

    private function company(User $user): Company
    {
        if (! $user->can(SubmitCompany::ABILITY)) {
            throw new RuntimeException('ثبت آگهی فقط برای کسی است که نقش کارفرمایش تأیید شده.');
        }

        $company = Company::query()->where('user_id', $user->getKey())->first();

        if ($company === null || ! $company->isListed()) {
            throw new RuntimeException('اول صفحه شرکت را بسازید؛ آگهی پس از تأیید صفحه شرکت ثبت می‌شود.');
        }

        return $company;
    }

    /** آگهی‌هایی که هنوز بسته یا منقضی نشده‌اند. */
    private function openCount(Company $company): int
    {
        return $company->postings()
            ->whereNull('closed_at')
            ->where(static fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now()))
            ->count();
    }
}
