<?php

declare(strict_types=1);

namespace App\Modules\Courses\Home;

use App\Contracts\HomepageSource;
use App\Modules\Courses\Domain\Course;
use App\Support\Home\HomeItem;
use App\Support\Home\HomeLayout;
use App\Support\Home\HomeSection;
use Illuminate\Support\Facades\Route;

/**
 * کاشی «دوره‌های تخصصی» صفحه اصلی، با تازه‌ترین دوره تأییدشده.
 *
 * شمار جلسه‌ها با `withCount` می‌آید تا کاشی یک پرس‌وجو بماند.
 */
final readonly class CourseHighlights implements HomepageSource
{
    private const LIMIT = 1;

    public function homeSection(): ?HomeSection
    {
        if (! Route::has('courses.show') || ! Route::has('courses.index')) {
            return null;
        }

        $courses = Course::query()
            ->published()
            ->withCount('sessions')
            ->orderByDesc('reviewed_at')
            ->limit(self::LIMIT)
            ->get();

        $items = $courses->map(fn (Course $course): HomeItem => new HomeItem(
            title: $course->title,
            url: route('courses.show', $course->slug),
            kicker: sprintf('دوره · %s جلسه', (string) $course->sessions_count),
            summary: $course->description ?? '',
            meta: $course->priceLabel(),
        ))->all();

        return new HomeSection(
            key: 'courses',
            title: 'دوره‌های تخصصی',
            lede: 'دوره‌های کوتاه و کاربردی با مدرسان تأییدشده؛ هر دوره پیش از انتشار بررسی می‌شود.',
            items: $items,
            order: 45,
            art: 'scene.class',
            moreUrl: route('courses.index'),
            moreLabel: 'دیدن دوره‌ها',
            layout: HomeLayout::Tile,
            icon: 'book',
        );
    }
}
