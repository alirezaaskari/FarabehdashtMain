<?php

declare(strict_types=1);

namespace App\Modules\Expert\Actions;

use App\Modules\Expert\Domain\Enums\ReviewStatus;
use App\Modules\Expert\Domain\ExpertAnswer;
use App\Modules\Expert\Events\AnswerPublished;
use App\Modules\Expert\Events\AnswerRejected;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * تصمیم مدیر درباره پاسخ در انتظار. برگرداندن بدون یادداشت پذیرفته نیست.
 */
final readonly class ReviewAnswer
{
    public function __construct(private Dispatcher $events) {}

    public function publish(ExpertAnswer $answer, int $adminId): ExpertAnswer
    {
        $this->assertPending($answer);

        $answer->forceFill([
            'status' => ReviewStatus::Published,
            'reviewed_by' => $adminId,
            'review_note' => null,
            'published_at' => Carbon::now(),
        ])->save();

        $this->events->dispatch(new AnswerPublished($answer, $adminId));

        return $answer;
    }

    public function reject(ExpertAnswer $answer, int $adminId, string $note): ExpertAnswer
    {
        $note = trim($note);

        if ($note === '') {
            throw new RuntimeException('یادداشتی بنویسید که مشاور بداند چه چیزی را اصلاح کند.');
        }

        $this->assertPending($answer);

        $answer->forceFill([
            'status' => ReviewStatus::Rejected,
            'reviewed_by' => $adminId,
            'review_note' => $note,
        ])->save();

        $this->events->dispatch(new AnswerRejected($answer, $adminId));

        return $answer;
    }

    private function assertPending(ExpertAnswer $answer): void
    {
        if ($answer->status !== ReviewStatus::Pending) {
            throw new RuntimeException('این پاسخ پیش‌تر بررسی شده است.');
        }
    }
}
