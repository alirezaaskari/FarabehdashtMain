<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Search;

use App\Contracts\SearchSource;
use App\Modules\Jobs\Domain\JobPosting;
use App\Support\Search\SearchGroup;
use App\Support\Search\SearchHit;
use App\Support\Search\SearchQuery;
use Illuminate\Support\Facades\Route;

/**
 * آگهی‌های زنده در جست‌وجوی سایت، بر پایه عنوان و شرح.
 */
final readonly class JobSearch implements SearchSource
{
    public function search(SearchQuery $query, int $limit): ?SearchGroup
    {
        if (! Route::has('jobs.show')) {
            return null;
        }

        $matches = $query->constrain(JobPosting::query()->live()->with('company'), ['title', 'description']);

        return new SearchGroup(
            key: 'jobs',
            title: 'آگهی‌های شغلی',
            hits: (clone $matches)->latest('published_at')->limit($limit)->get()
                ->map(static fn (JobPosting $posting): SearchHit => new SearchHit(
                    title: (string) $posting->title,
                    url: route('jobs.show', $posting->id),
                    summary: (string) $posting->company->name,
                ))
                ->values()
                ->all(),
            total: $matches->count(),
            order: 70,
        );
    }

    public function directUrl(SearchQuery $query): ?string
    {
        return null;
    }
}
