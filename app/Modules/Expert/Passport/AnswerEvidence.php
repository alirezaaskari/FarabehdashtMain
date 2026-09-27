<?php

declare(strict_types=1);

namespace App\Modules\Expert\Passport;

use App\Contracts\PassportEvidenceSource;
use App\Modules\Expert\Domain\Enums\ReviewStatus;
use App\Modules\Expert\Domain\ExpertAnswer;
use App\Support\Passport\PassportEvidence;

/**
 * پاسخ‌های منتشرشده کاربر در «پرسش از متخصص»، به تفکیک موضوع پرسش.
 */
final readonly class AnswerEvidence implements PassportEvidenceSource
{
    public function key(): string
    {
        return 'expert';
    }

    public function label(): string
    {
        return 'پاسخ‌های منتشرشده در پرسش از متخصص';
    }

    public function evidence(int $userId): array
    {
        return ExpertAnswer::query()
            ->where('user_id', $userId)
            ->where('status', ReviewStatus::Published)
            ->whereNotNull('published_at')
            ->with('question')
            ->latest('published_at')
            ->get()
            ->groupBy(static fn (ExpertAnswer $answer): string => $answer->question->topic->value)
            ->map(static fn ($answers): PassportEvidence => new PassportEvidence(
                title: 'موضوع '.$answers->first()->question->topic->label(),
                earnedAt: $answers->first()->published_at ?? $answers->first()->created_at,
                detail: 'پاسخ منتشرشده پس از بازبینی مدیر',
                count: $answers->count(),
                tags: ['expert-topic:'.$answers->first()->question->topic->value],
            ))
            ->values()
            ->all();
    }
}
