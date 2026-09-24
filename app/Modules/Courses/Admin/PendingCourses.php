<?php

declare(strict_types=1);

namespace App\Modules\Courses\Admin;

use App\Contracts\ApprovalQueueSource;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use App\Support\Admin\PendingItem;
use Illuminate\Support\Facades\Route;

/**
 * دوره‌ای که منتظر تصمیم مدیر است.
 */
final readonly class PendingCourses implements ApprovalQueueSource
{
    /** @return iterable<PendingItem> */
    public function pendingItems(): iterable
    {
        $url = Route::has('filament.fbh.pages.courses-review')
            ? route('filament.fbh.pages.courses-review')
            : url('/');

        $pending = Course::query()->where('status', CourseStatus::InReview->value)->cursor();

        foreach ($pending as $course) {
            yield new PendingItem(
                ability: 'admin.content.review',
                kind: 'course',
                title: 'انتشار دوره — '.$course->title,
                url: $url,
                waitingSince: $course->updated_at,
            );
        }

        foreach (Course::query()->withPendingChanges()->cursor() as $course) {
            yield new PendingItem(
                ability: 'admin.content.review',
                kind: 'course',
                title: 'افزوده تازه به دوره — '.$course->title,
                url: $url,
                waitingSince: $course->updated_at,
            );
        }
    }
}
