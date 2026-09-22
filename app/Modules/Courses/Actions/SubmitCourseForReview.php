<?php

declare(strict_types=1);

namespace App\Modules\Courses\Actions;

use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use RuntimeException;

final readonly class SubmitCourseForReview
{
    public function handle(Course $course): Course
    {
        if (! in_array($course->status, [CourseStatus::Draft, CourseStatus::Rejected], true)) {
            throw new RuntimeException('فقط دوره پیش‌نویس یا رد‌شده برای بررسی فرستاده می‌شود.');
        }

        if ($course->price_toman <= 0) {
            throw new RuntimeException('دوره بدون قیمت معتبر برای بررسی فرستاده نمی‌شود.');
        }

        if ($course->sessions->isEmpty()) {
            throw new RuntimeException('دوره‌ای بدون هیچ جلسه‌ای، ارزش بررسی ندارد؛ اول یک جلسه اضافه کنید.');
        }

        $course->forceFill(['status' => CourseStatus::InReview, 'review_note' => null])->save();

        return $course->refresh();
    }
}
