<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Actions;

use App\Modules\ExamPrep\Domain\Enums\AttemptMode;
use App\Modules\ExamPrep\Domain\PrepAnswer;
use App\Modules\ExamPrep\Domain\PrepAttempt;
use App\Modules\ExamPrep\Domain\PrepChoice;
use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;

/**
 * پاسخ‌دادن به سؤال‌ها.
 *
 * حالت پاسخ فوری (نمونه و تمرین) یک سؤال در هر بار می‌گیرد و پاسخ ثبت‌شده
 * عوض نمی‌شود. آزمون همه پاسخ‌ها را یک‌جا در پایان می‌گیرد؛ فرمی که بعد از
 * زمان به‌اضافه فرصت برسد «دیر» علامت می‌خورد و باز هم نمره می‌گیرد، تا
 * کندی شبکه کار داوطلب را پاک نکند.
 */
final readonly class RecordAnswer
{
    public function __construct(
        private ConnectionInterface $db,
        private int $graceSeconds,
    ) {}

    public function one(PrepAttempt $attempt, int $questionId, ?int $choiceId): PrepAnswer
    {
        if (! $attempt->mode->givesInstantFeedback()) {
            throw new InvalidArgumentException('پاسخ آزمون در پایان یک‌جا فرستاده می‌شود.');
        }

        return $this->db->transaction(function () use ($attempt, $questionId, $choiceId): PrepAnswer {
            $attempt = PrepAttempt::query()->lockForUpdate()->findOrFail($attempt->id);

            if ($attempt->isSubmitted() || ! in_array($questionId, $attempt->question_ids, true)) {
                throw new InvalidArgumentException('این سؤال در این دور باز نیست.');
            }

            $answer = PrepAnswer::query()->firstOrCreate(
                ['attempt_id' => $attempt->id, 'question_id' => $questionId],
                ['choice_id' => $choiceId, 'is_correct' => $this->isCorrect($questionId, $choiceId)],
            );

            if ($attempt->answers()->count() >= $attempt->total()) {
                $this->finish($attempt);
            }

            return $answer;
        });
    }

    /** @param  array<int|string, int|string|null>  $choices  شناسه سؤال ← شناسه گزینه */
    public function all(PrepAttempt $attempt, array $choices): PrepAttempt
    {
        if ($attempt->mode !== AttemptMode::Exam) {
            throw new InvalidArgumentException('این دور آزمون نیست.');
        }

        return $this->db->transaction(function () use ($attempt, $choices): PrepAttempt {
            $attempt = PrepAttempt::query()->lockForUpdate()->findOrFail($attempt->id);

            if ($attempt->isSubmitted()) {
                return $attempt;
            }

            foreach ($attempt->question_ids as $questionId) {
                $choiceId = isset($choices[$questionId]) && $choices[$questionId] !== '' ? (int) $choices[$questionId] : null;

                PrepAnswer::query()->updateOrCreate(
                    ['attempt_id' => $attempt->id, 'question_id' => $questionId],
                    ['choice_id' => $choiceId, 'is_correct' => $this->isCorrect($questionId, $choiceId)],
                );
            }

            $late = $attempt->deadline_at !== null && now()->greaterThan($attempt->deadline_at->copy()->addSeconds($this->graceSeconds));
            $attempt->late = $late;

            return $this->finish($attempt);
        });
    }

    private function finish(PrepAttempt $attempt): PrepAttempt
    {
        $attempt->forceFill([
            'submitted_at' => now(),
            'correct_count' => $attempt->answers()->where('is_correct', true)->count(),
        ])->save();

        return $attempt;
    }

    /** گزینه‌ای از سؤال دیگر یا ناموجود «غلط» است، نه خطا. */
    private function isCorrect(int $questionId, ?int $choiceId): bool
    {
        return $choiceId !== null && PrepChoice::query()
            ->whereKey($choiceId)
            ->where('question_id', $questionId)
            ->where('is_correct', true)
            ->exists();
    }
}
