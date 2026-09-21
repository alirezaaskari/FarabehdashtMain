<?php

declare(strict_types=1);

namespace App\Modules\Courses\Actions;

use App\Modules\Courses\Domain\Enrollment;
use App\Modules\Courses\Domain\Exam;
use App\Modules\Courses\Domain\ExamAttempt;
use App\Modules\Courses\Domain\ExamQuestion;
use App\Modules\Courses\Services\CourseCompletion;
use InvalidArgumentException;

/**
 * ثبت یک تلاش برای آزمون و نمره‌دهی — دانشجو می‌تواند دوباره تلاش کند؛
 * هر تلاش یک ردیف فقط‌افزودنی تازه است.
 */
final readonly class SubmitExamAttempt
{
    public function __construct(private CourseCompletion $completion) {}

    /** @param  array<int, int>  $answers  شناسه سؤال ← شناسه گزینه انتخابی */
    public function handle(Enrollment $enrollment, Exam $exam, array $answers): ExamAttempt
    {
        if ($exam->course_id !== $enrollment->course_id) {
            throw new InvalidArgumentException('این آزمون متعلق به این دوره نیست.');
        }

        if (! $enrollment->status->grantsAccess()) {
            throw new InvalidArgumentException('این ثبت‌نام دسترسی به آزمون ندارد.');
        }

        $questions = $exam->questions()->with('choices')->get();

        if ($questions->isEmpty()) {
            throw new InvalidArgumentException('این آزمون هنوز سؤالی ندارد.');
        }

        $correct = $questions->filter(
            fn (ExamQuestion $question): bool => $this->isAnsweredCorrectly($question, $answers[$question->id] ?? null),
        )->count();

        $score = (int) round($correct / $questions->count() * 100);
        $passed = $score >= $exam->pass_percentage;

        $attempt = ExamAttempt::query()->create([
            'enrollment_id' => $enrollment->id,
            'exam_id' => $exam->id,
            'score_percentage' => $score,
            'passed' => $passed,
        ]);

        $this->completion->evaluate($enrollment);

        return $attempt;
    }

    private function isAnsweredCorrectly(ExamQuestion $question, ?int $chosenChoiceId): bool
    {
        if ($chosenChoiceId === null) {
            return false;
        }

        $correctChoice = $question->choices->firstWhere('is_correct', true);

        return $correctChoice !== null && $correctChoice->id === $chosenChoiceId;
    }
}
