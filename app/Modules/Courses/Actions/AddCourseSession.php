<?php

declare(strict_types=1);

namespace App\Modules\Courses\Actions;

use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\CourseSession;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use RuntimeException;

/**
 * افزودن جلسه به دوره — همیشه در انتهای فهرست جای می‌گیرد.
 *
 * ترتیب‌دهی دستی (کشیدن و رهاکردن) در این بخش ساخته نشده؛ مدرس با ترتیب
 * افزودن جلسه‌ها کنار می‌آید (README ماژول).
 */
final readonly class AddCourseSession
{
    public function handle(Course $course, string $title, string $contentType, ?string $content): CourseSession
    {
        if ($course->status === CourseStatus::Retired) {
            throw new RuntimeException('دوره بازنشسته‌شده جلسه تازه نمی‌پذیرد.');
        }

        $nextPosition = 1 + (int) $course->sessions()->max('position');

        return CourseSession::query()->create([
            'course_id' => $course->id,
            'title' => $title,
            'content_type' => $contentType,
            'content' => $content,
            'position' => $nextPosition,
        ]);
    }
}
