<?php

declare(strict_types=1);

namespace App\Modules\Expert\Actions;

use App\Modules\Expert\Domain\Enums\ReviewStatus;
use App\Modules\Expert\Domain\ExpertQuestion;
use App\Modules\Expert\Events\QuestionPublished;
use App\Modules\Expert\Events\QuestionRejected;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * تصمیم مدیر درباره پرسش در انتظار. رد بدون دلیل پذیرفته نیست: پرسش‌کننده
 * باید بداند چرا پرسشش نرفت.
 */
final readonly class ReviewQuestion
{
    public function __construct(private Dispatcher $events) {}

    public function publish(ExpertQuestion $question, int $adminId): ExpertQuestion
    {
        $this->assertPending($question);

        $question->forceFill([
            'status' => ReviewStatus::Published,
            'reviewed_by' => $adminId,
            'review_note' => null,
            'published_at' => Carbon::now(),
        ])->save();

        $this->events->dispatch(new QuestionPublished($question, $adminId));

        return $question;
    }

    public function reject(ExpertQuestion $question, int $adminId, string $note): ExpertQuestion
    {
        $note = trim($note);

        if ($note === '') {
            throw new RuntimeException('دلیل رد را بنویسید؛ پرسش‌کننده همین را می‌بیند.');
        }

        $this->assertPending($question);

        $question->forceFill([
            'status' => ReviewStatus::Rejected,
            'reviewed_by' => $adminId,
            'review_note' => $note,
        ])->save();

        $this->events->dispatch(new QuestionRejected($question, $adminId));

        return $question;
    }

    private function assertPending(ExpertQuestion $question): void
    {
        if ($question->status !== ReviewStatus::Pending) {
            throw new RuntimeException('این پرسش پیش‌تر بررسی شده است.');
        }
    }
}
