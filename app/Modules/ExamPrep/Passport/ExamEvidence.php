<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Passport;

use App\Contracts\PassportEvidenceSource;
use App\Modules\ExamPrep\Domain\Enums\AttemptMode;
use App\Modules\ExamPrep\Domain\PrepAttempt;
use App\Support\Passport\PassportEvidence;

/**
 * بهترین کارنامه آزمون زمان‌دار هر بسته. تمرین و نمونه حساب نمی‌شوند و
 * کارنامه دیرتحویل هم نه؛ آستانه نمره (DEC-70) را خود گذرنامه اعمال می‌کند.
 */
final readonly class ExamEvidence implements PassportEvidenceSource
{
    public function key(): string
    {
        return 'exam_prep';
    }

    public function label(): string
    {
        return 'کارنامه آزمون‌های آمادگی';
    }

    public function evidence(int $userId): array
    {
        $best = [];

        $attempts = PrepAttempt::query()
            ->where('user_id', $userId)
            ->where('mode', AttemptMode::Exam)
            ->whereNotNull('submitted_at')
            ->where('late', false)
            ->with('pack')
            ->get();

        foreach ($attempts as $attempt) {
            $total = $attempt->total();

            if ($total === 0) {
                continue;
            }

            $score = intdiv($attempt->correct_count * 100, $total);
            $current = $best[$attempt->exam_pack_id] ?? null;

            if ($current === null || $score > $current[0]) {
                $best[$attempt->exam_pack_id] = [$score, $attempt];
            }
        }

        usort($best, static fn (array $a, array $b): int => $b[1]->submitted_at <=> $a[1]->submitted_at);

        return array_map(static fn (array $row): PassportEvidence => new PassportEvidence(
            title: $row[1]->pack->title,
            earnedAt: $row[1]->submitted_at ?? $row[1]->updated_at,
            detail: 'آزمون زمان‌دار '.$row[1]->pack->exam_name,
            tags: ['exam-pack:'.$row[1]->pack->slug],
            score: $row[0],
        ), $best);
    }
}
