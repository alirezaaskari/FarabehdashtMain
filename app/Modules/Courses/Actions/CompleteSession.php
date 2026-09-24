<?php

declare(strict_types=1);

namespace App\Modules\Courses\Actions;

use App\Modules\Courses\Domain\CourseSession;
use App\Modules\Courses\Domain\Enrollment;
use App\Modules\Courses\Domain\SessionProgress;
use App\Modules\Courses\Services\CourseCompletion;
use InvalidArgumentException;

/**
 * علامت‌زدن یک جلسه به‌عنوان دیده‌شده — دوباره صدازدن روی جلسه‌ای که قبلاً
 * تکمیل شده خطا نمی‌دهد، فقط همان ردیف را برمی‌گرداند.
 */
final readonly class CompleteSession
{
    public function __construct(private CourseCompletion $completion) {}

    public function handle(Enrollment $enrollment, CourseSession $session): SessionProgress
    {
        if ($session->course_id !== $enrollment->course_id) {
            throw new InvalidArgumentException('این جلسه متعلق به این دوره نیست.');
        }

        if (! $session->isApproved()) {
            throw new InvalidArgumentException('این جلسه هنوز تأیید نشده است.');
        }

        if (! $enrollment->status->grantsAccess()) {
            throw new InvalidArgumentException('این ثبت‌نام دسترسی به محیط یادگیری ندارد.');
        }

        $progress = SessionProgress::query()->firstOrCreate(
            ['enrollment_id' => $enrollment->id, 'course_session_id' => $session->id],
            ['completed_at' => now()],
        );

        $this->completion->evaluate($enrollment);

        return $progress;
    }
}
