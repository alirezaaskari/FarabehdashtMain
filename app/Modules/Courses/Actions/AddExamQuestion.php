<?php

declare(strict_types=1);

namespace App\Modules\Courses\Actions;

use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use App\Modules\Courses\Domain\Exam;
use App\Modules\Courses\Domain\ExamChoice;
use App\Modules\Courses\Domain\ExamQuestion;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * افزودن یک سؤال چندگزینه‌ای — آزمون دوره در صورت نبودن همین‌جا ساخته
 * می‌شود (حداکثر یک آزمون به ازای هر دوره).
 */
final readonly class AddExamQuestion
{
    public function __construct(private DatabaseManager $db) {}

    /** @param  list<array{text: string, is_correct: bool}>  $choices */
    public function handle(Course $course, string $text, array $choices): ExamQuestion
    {
        if (count($choices) < 2) {
            throw new InvalidArgumentException('هر سؤال دست‌کم به دو گزینه نیاز دارد.');
        }

        if (! (new Collection($choices))->contains('is_correct', true)) {
            throw new InvalidArgumentException('یکی از گزینه‌ها باید درست علامت بخورد.');
        }

        // افزوده به دوره منتشرشده منتظر تأیید مدیر می‌ماند (approved_at خالی).
        if ($course->status === CourseStatus::Published) {
            $course->touch();
        }

        return $this->db->transaction(function () use ($course, $text, $choices): ExamQuestion {
            $exam = Exam::query()->firstOrCreate(['course_id' => $course->id]);

            $position = 1 + (int) $exam->questions()->max('position');

            $question = ExamQuestion::query()->create([
                'exam_id' => $exam->id,
                'text' => $text,
                'position' => $position,
            ]);

            foreach ($choices as $choice) {
                ExamChoice::query()->create([
                    'exam_question_id' => $question->id,
                    'text' => $choice['text'],
                    'is_correct' => $choice['is_correct'],
                ]);
            }

            return $question->refresh();
        });
    }
}
