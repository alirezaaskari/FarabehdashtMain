<?php

declare(strict_types=1);

namespace App\Modules\Courses\Actions;

use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use App\Modules\Courses\Events\CoursePublished;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * انتشار یک دوره — تنها دری که وضعیت به «منتشرشده» می‌رسد (همان الگوی
 * `PublishProduct` ماژول تجارت).
 */
final readonly class PublishCourse
{
    public function __construct(private Dispatcher $events) {}

    public function handle(Course $course, ?int $actorId = null, ?Carbon $now = null): Course
    {
        $this->guard($course);

        $now ??= Carbon::now();

        $course->forceFill([
            'status' => CourseStatus::Published,
            'reviewed_at' => $now,
            'reviewed_by' => $actorId,
        ])->save();

        $published = $course->refresh();

        $this->events->dispatch(new CoursePublished($published, $actorId));

        return $published;
    }

    private function guard(Course $course): void
    {
        if ($course->status !== CourseStatus::InReview) {
            throw new RuntimeException('فقط دوره در انتظار بررسی منتشر می‌شود.');
        }

        if ($course->price_toman <= 0) {
            throw new RuntimeException('دوره بدون قیمت معتبر منتشر نمی‌شود.');
        }

        if ($course->sessions->isEmpty()) {
            throw new RuntimeException('دوره بدون هیچ جلسه‌ای منتشر نمی‌شود.');
        }
    }
}
