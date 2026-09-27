<?php

declare(strict_types=1);

namespace App\Modules\Courses\Passport;

use App\Contracts\PassportEvidenceSource;
use App\Modules\Courses\Domain\Enrollment;
use App\Modules\Courses\Domain\Enums\EnrollmentStatus;
use App\Support\Passport\PassportEvidence;
use Illuminate\Support\Facades\Route;

/**
 * دوره‌هایی که کاربر تا آخرین جلسه دیده است؛ دوره بازپرداخت‌شده حساب نمی‌شود.
 */
final readonly class CourseEvidence implements PassportEvidenceSource
{
    public function key(): string
    {
        return 'courses';
    }

    public function label(): string
    {
        return 'دوره‌های تمام‌شده';
    }

    public function evidence(int $userId): array
    {
        return Enrollment::query()
            ->where('student_user_id', $userId)
            ->where('status', EnrollmentStatus::Paid)
            ->whereNotNull('completed_at')
            ->with('course')
            ->latest('completed_at')
            ->get()
            ->map(static fn (Enrollment $enrollment): PassportEvidence => new PassportEvidence(
                title: $enrollment->course->title,
                earnedAt: $enrollment->completed_at ?? $enrollment->updated_at,
                tags: ['course:'.$enrollment->course->slug],
                url: Route::has('courses.show') ? route('courses.show', $enrollment->course->slug) : null,
            ))
            ->values()
            ->all();
    }
}
