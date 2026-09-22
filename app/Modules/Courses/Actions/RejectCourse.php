<?php

declare(strict_types=1);

namespace App\Modules\Courses\Actions;

use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use App\Modules\Courses\Events\CourseRejected;
use Illuminate\Contracts\Events\Dispatcher;
use RuntimeException;

final readonly class RejectCourse
{
    public function __construct(private Dispatcher $events) {}

    public function handle(Course $course, int $actorId, string $note): Course
    {
        if ($course->status !== CourseStatus::InReview) {
            throw new RuntimeException('فقط دوره در انتظار بررسی رد می‌شود.');
        }

        if (trim($note) === '') {
            throw new RuntimeException('رد دوره بدون یادداشت برای مدرس انجام نمی‌شود.');
        }

        $course->forceFill([
            'status' => CourseStatus::Rejected,
            'review_note' => $note,
            'reviewed_at' => now(),
            'reviewed_by' => $actorId,
        ])->save();

        $rejected = $course->refresh();

        $this->events->dispatch(new CourseRejected($rejected, $actorId, $note));

        return $rejected;
    }
}
