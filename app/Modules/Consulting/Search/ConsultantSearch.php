<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Search;

use App\Contracts\SearchSource;
use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Support\Search\SearchGroup;
use App\Support\Search\SearchHit;
use App\Support\Search\SearchQuery;
use Illuminate\Support\Facades\Route;

/**
 * مشاوران و آزمایشگاه‌های منتشرشده در جست‌وجوی سایت، بر پایه نام، عنوان و معرفی.
 */
final readonly class ConsultantSearch implements SearchSource
{
    public function search(SearchQuery $query, int $limit): ?SearchGroup
    {
        if (! Route::has('consulting.show')) {
            return null;
        }

        $matches = $query->constrain(ConsultantProfile::query()->listed(), ['display_name', 'headline', 'bio']);

        return new SearchGroup(
            key: 'consultants',
            title: 'مشاوران و آزمایشگاه‌ها',
            hits: (clone $matches)->orderBy('display_name')->limit($limit)->get()
                ->map(static fn (ConsultantProfile $profile): SearchHit => new SearchHit(
                    title: (string) $profile->display_name,
                    url: $profile->publicUrl(),
                    summary: (string) $profile->headline,
                ))
                ->values()
                ->all(),
            total: $matches->count(),
            order: 65,
        );
    }

    public function directUrl(SearchQuery $query): ?string
    {
        return null;
    }
}
