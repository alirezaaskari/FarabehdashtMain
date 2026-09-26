<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\ExamPrep\Domain\Enums\QuestionStatus;
use App\Modules\ExamPrep\Domain\PrepQuestion;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * سؤال نویسنده تأیید یا برای اصلاح برگردانده شد. نویسنده (مدرس) اعلان
 * می‌گیرد؛ سؤالی که مدیر خودش وارد کرده نویسنده جدایی ندارد.
 */
final readonly class QuestionReviewed implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public PrepQuestion $question,
        public int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: $this->published() ? 'exam_prep.question_published' : 'exam_prep.question_rejected',
            subjectType: PrepQuestion::class,
            subjectId: $this->question->uuid,
            actorId: $this->actorId,
            after: ['status' => $this->question->status->value],
            context: ['author_user_id' => $this->question->author_user_id, 'note' => $this->question->review_note],
        );
    }

    public function userNotices(): array
    {
        if ($this->question->author_user_id === null || $this->question->author_user_id === $this->actorId) {
            return [];
        }

        $pack = $this->question->pack;

        return [new UserNotice(
            recipientId: $this->question->author_user_id,
            kind: $this->published() ? 'exam_prep.question_published' : 'exam_prep.question_rejected',
            title: $this->published()
                ? sprintf('سؤال شما در بسته «%s» منتشر شد', $pack->title)
                : sprintf('سؤال شما در بسته «%s» برای اصلاح برگشت', $pack->title),
            body: $this->published() ? null : $this->question->review_note,
            routeName: 'exam_prep.writer.index',
        )];
    }

    private function published(): bool
    {
        return $this->question->status === QuestionStatus::Published;
    }
}
