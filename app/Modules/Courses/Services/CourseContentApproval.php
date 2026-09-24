<?php

declare(strict_types=1);

namespace App\Modules\Courses\Services;

use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\CourseSession;
use App\Modules\Courses\Domain\ExamQuestion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * جلسه‌ها و سؤال‌های در انتظار تأیید یک دوره.
 *
 * هر جلسه و سؤال جدا تأیید می‌شود: دوره منتشرشده سر جایش می‌ماند و فقط
 * افزوده تازه تا تصمیم مدیر از دانشجو پنهان است. انتشار خود دوره همه را
 * یک‌جا تأیید می‌کند.
 */
final readonly class CourseContentApproval
{
    /** @return array{sessions: int, questions: int} */
    public function pendingCounts(Course $course): array
    {
        return [
            'sessions' => $this->pendingSessions($course)->count(),
            'questions' => $this->pendingQuestions($course)->count(),
        ];
    }

    public function hasPending(Course $course): bool
    {
        return $this->pendingSessions($course)->exists() || $this->pendingQuestions($course)->exists();
    }

    /** @return array{sessions: int, questions: int} */
    public function approve(Course $course, ?Carbon $now = null): array
    {
        $now ??= Carbon::now();

        return [
            'sessions' => $this->pendingSessions($course)->update(['approved_at' => $now]),
            'questions' => $this->pendingQuestions($course)->update(['approved_at' => $now]),
        ];
    }

    /**
     * افزوده ردشده پاک می‌شود، نه اینکه پنهان بماند: در صف نمی‌ماند و مدرس
     * نسخه اصلاح‌شده را دوباره اضافه می‌کند. هنوز هیچ دانشجویی آن را ندیده.
     *
     * @return array{sessions: int, questions: int}
     */
    public function discard(Course $course): array
    {
        return [
            'sessions' => $this->pendingSessions($course)->delete(),
            'questions' => $this->pendingQuestions($course)->delete(),
        ];
    }

    /** @return Builder<CourseSession> */
    private function pendingSessions(Course $course): Builder
    {
        return CourseSession::query()->where('course_id', $course->id)->pendingApproval();
    }

    /** @return Builder<ExamQuestion> */
    private function pendingQuestions(Course $course): Builder
    {
        return ExamQuestion::query()
            ->whereHas('exam', static fn (Builder $exam) => $exam->where('course_id', $course->id))
            ->pendingApproval();
    }
}
