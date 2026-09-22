<?php

declare(strict_types=1);

namespace App\Modules\Courses\Home;

use App\Contracts\HomepageSource;
use App\Modules\Courses\Domain\Course;
use App\Support\Home\HomeItem;
use App\Support\Home\HomeSection;
use Illuminate\Support\Facades\Route;

/**
 * تازه‌ترین دوره‌های تأییدشده، برای صفحه اصلی.
 *
 * شمار جلسه‌ها با `withCount` می‌آید تا فهرست چهارتایی یک پرس‌وجو بماند.
 */
final readonly class CourseHighlights implements HomepageSource
{
    private const LIMIT = 4;

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
            meta: $course->price()->format(),
        ))->all();

        return new HomeSection(
            key: 'courses',
            title: 'دوره‌های آموزشی',
            lede: 'هر دوره پیش از انتشار توسط مدیر بررسی می‌شود.',
            items: $items,
            order: 50,
            moreUrl: route('courses.index'),
            moreLabel: 'مشاهده دوره‌ها',
        );
    }
}
