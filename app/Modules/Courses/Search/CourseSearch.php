<?php

declare(strict_types=1);

namespace App\Modules\Courses\Search;

use App\Contracts\SearchSource;
use App\Modules\Courses\Domain\Course;
use App\Support\Search\SearchGroup;
use App\Support\Search\SearchHit;
use App\Support\Search\SearchQuery;
use Illuminate\Support\Facades\Route;

/**
 * دوره‌های منتشرشده در جست‌وجوی داخلی.
 */
final readonly class CourseSearch implements SearchSource
{
    public function search(SearchQuery $query, int $limit): ?SearchGroup
    {
        if (! Route::has('courses.show')) {
            return null;
        }

        $matches = $query->constrain(Course::query()->published(), ['title', 'description']);

        return new SearchGroup(
            key: 'courses',
            title: 'دوره‌ها',
            hits: (clone $matches)->orderBy('title')->limit($limit)->get()
                ->map(static fn (Course $course): SearchHit => new SearchHit(
                    title: $course->title,
                    url: route('courses.show', $course->slug),
                    summary: (string) $course->description,
                ))
                ->values()
                ->all(),
            total: $matches->count(),
            order: 30,
            moreUrl: Route::has('courses.index') ? route('courses.index', ['q' => $query->raw]) : null,
        );
    }

    public function directUrl(SearchQuery $query): ?string
    {
        return null;
    }
}
