<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Services;

use App\Contracts\SearchSource;
use App\Support\Search\SearchGroup;
use App\Support\Search\SearchQuery;

/**
 * جست‌وجوی داخلی روی همه ماژول‌های ثبت‌شده (DEC-25).
 *
 * هر ماژول جدول خودش را می‌گردد؛ این‌جا فقط جمع و مرتب می‌شود. نبودن یک
 * ماژول یعنی نبودن گروهش، نه خطا.
 */
final readonly class SiteSearch
{
    /** @param  iterable<SearchSource>  $sources */
    public function __construct(
        private iterable $sources,
        private int $perGroup,
    ) {}

    /**
     * @param  int|null  $perGroup  سهم هر بخش؛ پیشنهاد فوری سربرگ کمتر از صفحه نتایج می‌خواهد.
     * @return list<SearchGroup>
     */
    public function search(SearchQuery $query, ?int $perGroup = null): array
    {
        if (! $query->isSearchable()) {
            return [];
        }

        $groups = [];

        foreach ($this->sources as $source) {
            $group = $source->search($query, $perGroup ?? $this->perGroup);

            if ($group instanceof SearchGroup && $group->hits !== []) {
                $groups[] = $group;
            }
        }

        usort($groups, static fn (SearchGroup $a, SearchGroup $b): int => $a->order <=> $b->order);

        return $groups;
    }

    /** نخستین مقصد مستقیم، اگر عبارت شناسه یکتای یکی از ماژول‌ها باشد. */
    public function directUrl(SearchQuery $query): ?string
    {
        if (! $query->isSearchable()) {
            return null;
        }

        foreach ($this->sources as $source) {
            $url = $source->directUrl($query);

            if ($url !== null) {
                return $url;
            }
        }

        return null;
    }
}
