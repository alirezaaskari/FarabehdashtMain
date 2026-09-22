<?php

declare(strict_types=1);

namespace App\Modules\Courses\Services;

use App\Modules\Courses\Domain\Enrollment;

/**
 * آیا یک ثبت‌نام تکمیل شده — همه جلسه‌ها دیده‌شده و (اگر دوره آزمون دارد)
 * آزمون قبول شده.
 *
 * لحظه تکمیل یک‌بار ثبت و هرگز بازنویسی نمی‌شود؛ اگر مدرس بعداً جلسه تازه
 * اضافه کند، تکمیل قبلی دانشجو باطل نمی‌شود — همان فلسفه Snapshot که در
 * قیمت و کمیسیون این پروژه هم هست.
 */
final readonly class CourseCompletion
{
    public function evaluate(Enrollment $enrollment): void
    {
        if ($enrollment->completed_at !== null) {
            return;
        }

        $totalSessions = $enrollment->course->sessions()->count();

        if ($totalSessions === 0 || $enrollment->progress()->count() < $totalSessions) {
            return;
        }

        $exam = $enrollment->course->exam;

        if ($exam !== null && ! $enrollment->examAttempts()->where('passed', true)->exists()) {
            return;
        }

        $enrollment->forceFill(['completed_at' => now()])->save();
    }
}
