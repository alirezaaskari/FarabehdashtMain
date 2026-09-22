<?php

declare(strict_types=1);

namespace App\Modules\Courses\Http\Controllers;

use App\Modules\Courses\Actions\CompleteSession;
use App\Modules\Courses\Actions\SubmitCourseReview;
use App\Modules\Courses\Actions\SubmitExamAttempt;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\CourseSession;
use App\Modules\Courses\Domain\Enrollment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * محیط یادگیری — فقط برای دانشجوی ثبت‌نام‌شده و پرداخت‌کرده.
 */
final readonly class LearnController
{
    public function __construct(
        private CompleteSession $completeSession,
        private SubmitExamAttempt $submitExamAttempt,
        private SubmitCourseReview $submitReview,
    ) {}

    public function show(Request $request, Course $course): View
    {
        $enrollment = $this->enrollmentFor($request, $course);
        $enrollment->load(['progress', 'examAttempts', 'review']);
        $course->load(['sessions', 'exam.questions.choices']);

        return view('courses::learn', ['course' => $course, 'enrollment' => $enrollment]);
    }

    public function completeSession(Request $request, Course $course, CourseSession $session): RedirectResponse
    {
        $enrollment = $this->enrollmentFor($request, $course);

        try {
            $this->completeSession->handle($enrollment, $session);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['session' => $exception->getMessage()]);
        }

        return redirect()->route('courses.learn', $course);
    }

    public function submitExam(Request $request, Course $course): RedirectResponse
    {
        $enrollment = $this->enrollmentFor($request, $course);
        $exam = $course->exam;

        abort_if($exam === null, 404, 'این دوره آزمونی ندارد.');

        /** @var array<int, int> $answers */
        $answers = (new Collection((array) $request->input('answers', [])))
            ->mapWithKeys(fn (mixed $choiceId, int|string $questionId): array => [(int) $questionId => (int) $choiceId])
            ->all();

        try {
            $this->submitExamAttempt->handle($enrollment, $exam, $answers);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['exam' => $exception->getMessage()]);
        }

        return redirect()->route('courses.learn', $course);
    }

    public function submitReview(Request $request, Course $course): RedirectResponse
    {
        $enrollment = $this->enrollmentFor($request, $course);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->submitReview->handle($enrollment, (int) $data['rating'], $data['comment'] ?? null);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['review' => $exception->getMessage()]);
        }

        return redirect()->route('courses.learn', $course);
    }

    private function enrollmentFor(Request $request, Course $course): Enrollment
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $enrollment = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('student_user_id', $user->id)
            ->first();

        abort_if($enrollment === null || ! $enrollment->status->grantsAccess(), 403, 'دسترسی به این دوره ندارید.');

        return $enrollment;
    }
}
