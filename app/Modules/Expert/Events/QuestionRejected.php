<?php

declare(strict_types=1);

namespace App\Modules\Expert\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Expert\Domain\ExpertQuestion;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * مدیر پرسش را رد کرد؛ پرسش‌کننده یادداشت مدیر را می‌بیند.
 */
final readonly class QuestionRejected implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public ExpertQuestion $question,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'expert.question_rejected',
            subjectType: ExpertQuestion::class,
            subjectId: $this->question->uuid,
            actorId: $this->actorId,
            after: ['status' => $this->question->status->value],
        );
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->question->user_id,
            kind: 'expert.question_rejected',
            title: 'پرسش شما منتشر نشد',
            body: $this->question->review_note,
            routeName: 'expert.mine',
        )];
    }
}
