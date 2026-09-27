<?php

declare(strict_types=1);

namespace App\Modules\Expert\Services;

use App\Contracts\ExpertAnswerDirectory;
use App\Modules\Expert\Domain\ExpertAnswer;
use App\Support\Expert\AnswerLink;
use Illuminate\Database\Eloquent\Builder;

/**
 * پاسخ‌های منتشرشده یک مشاور زیر پرسش‌های عمومی، برای صفحه عمومی او.
 */
final readonly class PublishedAnswers implements ExpertAnswerDirectory
{
    public function publishedBy(int $userId, int $limit): array
    {
        return $this->query($userId)
            ->with('question')
            ->latest('published_at')
            ->limit($limit)
            ->get()
            ->map(static fn (ExpertAnswer $answer): AnswerLink => new AnswerLink(
                questionTitle: $answer->question->title,
                url: route('expert.show', $answer->question->uuid).'#answer-'.$answer->uuid,
                publishedAt: $answer->published_at ?? $answer->created_at,
                accepted: $answer->question->accepted_answer_id === $answer->id,
            ))
            ->values()
            ->all();
    }

    public function countPublishedBy(int $userId): int
    {
        return $this->query($userId)->count();
    }

    /** @return Builder<ExpertAnswer> */
    private function query(int $userId): Builder
    {
        return ExpertAnswer::query()
            ->published()
            ->where('user_id', $userId)
            ->whereHas('question', static fn (Builder $query) => $query->listed());
    }
}
