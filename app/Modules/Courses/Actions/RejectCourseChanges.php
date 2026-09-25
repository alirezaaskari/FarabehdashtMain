<?php

declare(strict_types=1);

namespace App\Modules\Courses\Actions;

use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use App\Modules\Courses\Events\CourseChangesRejected;
use App\Modules\Courses\Services\CourseContentApproval;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;
use RuntimeException;

/**
 * رد افزوده‌های تازه یک دوره منتشرشده. خود دوره منتشر می‌ماند؛ فقط افزوده‌ها
 * پاک می‌شوند و دلیل برای مدرس فرستاده می‌شود.
 */
final readonly class RejectCourseChanges
{
    public function __construct(
        private CourseContentApproval $approval,
        private Dispatcher $events,
    ) {}

    public function handle(Course $course, int $actorId, string $note): void
    {
        if (trim($note) === '') {
            throw new InvalidArgumentException('رد بدون یادداشت ممکن نیست؛ مدرس باید بداند چه چیزی را اصلاح کند.');
        }

        if ($course->status !== CourseStatus::Published || ! $this->approval->hasPending($course)) {
            throw new RuntimeException('این دوره تغییر در انتظار تأییدی ندارد.');
        }

        $counts = $this->approval->discard($course);

        $this->events->dispatch(new CourseChangesRejected($course, $actorId, trim($note), $counts));
    }
}
