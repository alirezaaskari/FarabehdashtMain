<?php

declare(strict_types=1);

namespace App\Modules\Courses\Actions;

use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use App\Modules\Courses\Events\CourseChangesApproved;
use App\Modules\Courses\Services\CourseContentApproval;
use Illuminate\Contracts\Events\Dispatcher;
use RuntimeException;

/**
 * تأیید جلسه‌ها و سؤال‌هایی که مدرس به دوره منتشرشده افزوده؛ از این لحظه
 * دانشجو آن‌ها را می‌بیند.
 */
final readonly class ApproveCourseChanges
{
    public function __construct(
        private CourseContentApproval $approval,
        private Dispatcher $events,
    ) {}

    public function handle(Course $course, int $actorId): void
    {
        if ($course->status !== CourseStatus::Published || ! $this->approval->hasPending($course)) {
            throw new RuntimeException('این دوره تغییر در انتظار تأییدی ندارد.');
        }

        $counts = $this->approval->approve($course);

        $this->events->dispatch(new CourseChangesApproved($course, $actorId, $counts));
    }
}
