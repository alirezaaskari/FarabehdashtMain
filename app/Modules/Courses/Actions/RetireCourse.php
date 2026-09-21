<?php

declare(strict_types=1);

namespace App\Modules\Courses\Actions;

use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use RuntimeException;

/**
 * خروج دوره از ثبت‌نام — تصمیم خود مدرس، بدون نیاز به تأیید. دانشجوهای
 * ثبت‌نام‌شده دسترسی محیط یادگیری‌شان را نگه می‌دارند.
 */
final readonly class RetireCourse
{
    public function handle(Course $course): Course
    {
        if ($course->status !== CourseStatus::Published) {
            throw new RuntimeException('فقط دوره منتشرشده بازنشسته می‌شود.');
        }

        $course->forceFill(['status' => CourseStatus::Retired])->save();

        return $course->refresh();
    }
}
