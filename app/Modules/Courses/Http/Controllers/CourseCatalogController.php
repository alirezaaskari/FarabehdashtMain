<?php

declare(strict_types=1);

namespace App\Modules\Courses\Http\Controllers;

use App\Modules\Courses\Domain\Course;
use App\Support\Seo\Schema;
use App\Support\Seo\SeoMeta;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * فهرست و صفحه معرفی دوره — فقط منتشرشده نشانی عمومی دارد.
 */
final readonly class CourseCatalogController
{
    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));

        $courses = Course::query()
            ->published()
            ->when($query !== '', fn ($q) => $q->where('title', 'like', '%'.$query.'%'))
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('courses::index', ['courses' => $courses, 'query' => $query]);
    }

    public function show(string $slug): View
    {
        $course = Course::query()->published()->where('slug', $slug)->with(['sessions' => static fn ($sessions) => $sessions->approved()])->first();

        if ($course === null) {
            throw new NotFoundHttpException('این دوره پیدا نشد.');
        }

        return view('courses::show', ['course' => $course, 'seo' => $this->seo($course)]);
    }

    private function seo(Course $course): SeoMeta
    {
        $url = route('courses.show', $course->slug);
        $description = $course->description ?? $course->title;

        $meta = new SeoMeta(title: $course->title, description: $course->description, canonical: $url);

        // Course و نه گواهی: گذراندن دوره مدرک رسمی نیست و Schema هم نمی‌گوید.
        return $meta->withSchema(Schema::graph(
            Schema::course($course->title, $url, $description, price: $course->price()),
            Schema::breadcrumbs([
                ['name' => 'دوره‌ها', 'url' => route('courses.index')],
                ['name' => $course->title, 'url' => $url],
            ]),
        ));
    }
}
