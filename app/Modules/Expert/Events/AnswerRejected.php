<?php

declare(strict_types=1);

namespace App\Modules\Expert\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Expert\Domain\ExpertAnswer;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * مدیر پاسخ را برگرداند؛ مشاور با یادداشت مدیر اصلاحش می‌کند.
 */
final readonly class AnswerRejected implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public ExpertAnswer $answer,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'expert.answer_rejected',
            subjectType: ExpertAnswer::class,
            subjectId: $this->answer->uuid,
            actorId: $this->actorId,
            after: ['status' => $this->answer->status->value],
            context: ['question_id' => $this->answer->question_id],
        );
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->answer->user_id,
            kind: 'expert.answer_rejected',
            title: 'پاسخ شما برای اصلاح برگشت',
            body: $this->answer->review_note,
            routeName: 'expert.show',
            routeParameters: ['uuid' => $this->answer->question->uuid],
        )];
    }
}
