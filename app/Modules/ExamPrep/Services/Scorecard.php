<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Services;

use App\Modules\ExamPrep\Domain\PrepAnswer;
use App\Modules\ExamPrep\Domain\PrepAttempt;
use App\Modules\ExamPrep\Domain\PrepQuestion;
use Illuminate\Support\Collection;

/**
 * کارنامه یک دور: نمره کل، درصد هر موضوع و فهرست پاسخ‌های غلط با پاسخ
 * درست، توضیح و پیوند مطالعه.
 *
 * موضوعی با درصد کمتر از {@see WEAK_BELOW} «نیازمند مرور» نشان داده می‌شود؛
 * سؤال بی‌پاسخ هم غلط شمرده می‌شود.
 */
final readonly class Scorecard
{
    public const WEAK_BELOW = 60;

    /**
     * @return array{
     *     correct: int,
     *     total: int,
     *     percent: int,
     *     topics: list<array{title: string, correct: int, total: int, percent: int, weak: bool}>,
     *     mistakes: list<array{question: PrepQuestion, chosen: ?string, correct: ?string}>
     * }
     */
    public function for(PrepAttempt $attempt): array
    {
        $questions = PrepQuestion::query()
            ->whereIn('id', $attempt->question_ids)
            ->with(['choices', 'topic'])
            ->get()
            ->keyBy('id');

        /** @var Collection<int, PrepAnswer> $answers */
        $answers = $attempt->answers()->get()->keyBy('question_id');

        $topics = [];
        $mistakes = [];
        $correct = 0;

        foreach ($attempt->question_ids as $id) {
            $question = $questions->get($id);

            if ($question === null) {
                continue;
            }

            $answer = $answers->get($id);
            $right = $answer?->is_correct === true;
            $title = $question->topic->title;

            $topics[$title] ??= ['title' => $title, 'correct' => 0, 'total' => 0];
            $topics[$title]['total']++;

            if ($right) {
                $correct++;
                $topics[$title]['correct']++;

                continue;
            }

            $mistakes[] = [
                'question' => $question,
                'chosen' => $answer?->choice_id !== null ? $question->choices->firstWhere('id', $answer->choice_id)?->body : null,
                'correct' => $question->correctChoice()?->body,
            ];
        }

        $rows = array_map(static function (array $row): array {
            $percent = self::percent($row['correct'], $row['total']);

            return [...$row, 'percent' => $percent, 'weak' => $percent < self::WEAK_BELOW];
        }, array_values($topics));

        usort($rows, static fn (array $a, array $b): int => $a['percent'] <=> $b['percent']);

        $total = $questions->count();

        return [
            'correct' => $correct,
            'total' => $total,
            'percent' => self::percent($correct, $total),
            'topics' => $rows,
            'mistakes' => $mistakes,
        ];
    }

    private static function percent(int $correct, int $total): int
    {
        return $total === 0 ? 0 : (int) round($correct * 100 / $total);
    }
}
