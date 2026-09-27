<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Seo;

use App\Contracts\SitemapSource;
use App\Modules\Jobs\Domain\Company;
use App\Modules\Jobs\Domain\JobPosting;
use App\Modules\Jobs\Services\JobCatalog;
use App\Support\Seo\SitemapUrl;
use Illuminate\Support\Facades\Route;

/**
 * صفحه‌های کاریابی که ایندکس‌پذیرند: فهرست، هر آگهی زنده (آگهی منقضی noindex
 * است، DEC-66)، صفحه شرکت‌ها، و صفحه شهر یا مهارتی که دست‌کم دو آگهی زنده دارد.
 */
final readonly class JobSitemapSource implements SitemapSource
{
    public function __construct(private JobCatalog $catalog) {}

    public function section(): string
    {
        return 'jobs';
    }

    public function sitemapUrls(): iterable
    {
        if (! Route::has('jobs.index')) {
            return;
        }

        yield new SitemapUrl(route('jobs.index'));

        foreach (JobPosting::query()->live()->latest('published_at')->cursor() as $posting) {
            yield new SitemapUrl(route('jobs.show', $posting->id), $posting->updated_at);
        }

        foreach (Company::query()->listed()->cursor() as $company) {
            yield new SitemapUrl(route('jobs.companies.show', $company->slug), $company->updated_at);
        }

        foreach ($this->catalog->cityCounts() as $city => $count) {
            if ($this->catalog->isIndexable($count)) {
                yield new SitemapUrl(route('jobs.city', $city));
            }
        }

        foreach ($this->catalog->skillCounts() as $skill => $count) {
            if ($this->catalog->isIndexable($count)) {
                yield new SitemapUrl(route('jobs.skill', $skill));
            }
        }
    }
}
