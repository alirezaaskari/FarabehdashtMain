<?php

declare(strict_types=1);

namespace App\Modules\Courses\Seo;

use App\Contracts\SitemapSource;
use App\Modules\Courses\Domain\Course;
use App\Support\Seo\SitemapUrl;
use Illuminate\Support\Facades\Route;

/**
 * نشانی‌های دوره‌ها برای نقشه سایت — فقط منتشرشده.
 */
final readonly class CourseSitemapSource implements SitemapSource
{
    public function section(): string
    {
        return 'courses';
    }

    /** @return iterable<SitemapUrl> */
    public function sitemapUrls(): iterable
    {
        if (! Route::has('courses.show')) {
            return;
        }

        yield new SitemapUrl(route('courses.index'));

        foreach (Course::query()->published()->orderBy('id')->cursor() as $course) {
            yield new SitemapUrl(route('courses.show', $course->slug), $course->updated_at);
        }
    }
}
