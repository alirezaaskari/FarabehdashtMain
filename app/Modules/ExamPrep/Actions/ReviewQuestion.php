<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Actions;

use App\Modules\ExamPrep\Domain\Enums\QuestionStatus;
use App\Modules\ExamPrep\Domain\PrepQuestion;
use App\Modules\ExamPrep\Events\QuestionReviewed;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;

/** تصمیم مدیر روی سؤال مدرس: انتشار، یا برگرداندن با یادداشت. */
final readonly class ReviewQuestion
{
    public function __construct(private Dispatcher $events) {}

    public function publish(PrepQuestion $question, int $actorId): PrepQuestion
    {
        return $this->decide($question, QuestionStatus::Published, null, $actorId);
    }

    public function reject(PrepQuestion $question, string $note, int $actorId): PrepQuestion
    {
        $note = trim($note);

        if ($note === '') {
            throw new InvalidArgumentException('بنویسید چه چیزی باید اصلاح شود؛ نویسنده همین را می‌بیند.');
        }

        return $this->decide($question, QuestionStatus::Rejected, $note, $actorId);
    }

    private function decide(PrepQuestion $question, QuestionStatus $to, ?string $note, int $actorId): PrepQuestion
    {
        if ($question->status !== QuestionStatus::Pending) {
            throw new InvalidArgumentException('این سؤال دیگر در انتظار بررسی نیست.');
        }

        $question->forceFill([
            'status' => $to,
            'review_note' => $note,
            'published_at' => $to === QuestionStatus::Published ? now() : null,
        ])->save();

        $this->events->dispatch(new QuestionReviewed($question, $actorId));

        return $question;
    }
}
