<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Admin;

use App\Contracts\ApprovalQueueSource;
use App\Modules\Jobs\Domain\Company;
use App\Modules\Jobs\Domain\Enums\ReviewStatus;
use App\Modules\Jobs\Domain\JobPosting;
use App\Support\Admin\PendingItem;
use Illuminate\Support\Facades\Route;

/**
 * صفحه‌های شرکت و آگهی‌های در انتظار مدیر، برای صف یکپارچه داشبورد پنل.
 */
final readonly class PendingJobItems implements ApprovalQueueSource
{
    public const ABILITY = 'admin.jobs.review';

    /** @return iterable<PendingItem> */
    public function pendingItems(): iterable
    {
        $url = Route::has('filament.fbh.pages.job-review') ? route('filament.fbh.pages.job-review') : url('/');

        foreach (Company::query()->where('status', ReviewStatus::Pending)->oldest('submitted_at')->cursor() as $company) {
            yield new PendingItem(
                ability: self::ABILITY,
                kind: 'company',
                title: ($company->published_at === null ? 'صفحه شرکت تازه — ' : 'ویرایش صفحه شرکت — ').($company->pending['name'] ?? ''),
                url: $url,
                waitingSince: $company->submitted_at ?? $company->updated_at,
            );
        }

        foreach (JobPosting::query()->where('status', ReviewStatus::Pending)->oldest('submitted_at')->cursor() as $posting) {
            yield new PendingItem(
                ability: self::ABILITY,
                kind: 'job_posting',
                title: ($posting->approved_at === null ? 'آگهی شغلی تازه — ' : 'ویرایش آگهی شغلی — ').($posting->pending['title'] ?? ''),
                url: $url,
                waitingSince: $posting->submitted_at ?? $posting->updated_at,
            );
        }
    }
}
