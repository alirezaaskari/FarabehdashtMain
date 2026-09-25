<?php

declare(strict_types=1);

namespace App\Modules\Courses\Services;

use App\Modules\Courses\Domain\Enrollment;
use App\Modules\Courses\Domain\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Collection;

/**
 * دوره‌هایی که کاربر در آن‌ها ثبت‌نام کرده، با پیشرفتش.
 *
 * فقط ثبت‌نام پرداخت‌شده می‌آید: پرداخت رهاشده دوره‌ای نیست که کاربر
 * «دارد». جلسه‌های در انتظار تأیید در مخرج پیشرفت نیستند، همان‌طور که در
 * {@see CourseCompletion}.
 */
final readonly class LearnerCourses
{
    /** @return Collection<int, Enrollment> */
    public function for(int $userId): Collection
    {
        return Enrollment::query()
            ->forStudent($userId)
            ->where('status', EnrollmentStatus::Paid->value)
            ->withCount('progress')
            ->with(['course' => static fn ($course) => $course->withCount([
                'sessions as approved_sessions_count' => static fn ($sessions) => $sessions->approved(),
            ])])
            // دوره نیمه‌تمام بالای فهرست: همان است که کاربر برای ادامه‌اش آمده.
            ->orderByRaw('completed_at is not null')
            ->latest('paid_at')
            ->get();
    }
}
