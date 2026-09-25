<?php

declare(strict_types=1);

namespace App\Modules\Expert\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Expert\Domain\ExpertAnswer;
use App\Modules\Expert\Domain\ExpertQuestion;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * پرسش‌کننده این پاسخ را بهترین پاسخ دانست؛ پرسش «پاسخ‌گرفته» شد.
 */
final readonly class AnswerAccepted implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public ExpertQuestion $question,
        public ExpertAnswer $answer,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'expert.answer_accepted',
            subjectType: ExpertQuestion::class,
            subjectId: $this->question->uuid,
            actorId: $this->question->user_id,
            after: ['accepted_answer' => $this->answer->uuid],
        );
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->answer->user_id,
            kind: 'expert.answer_accepted',
            title: 'پاسخ شما بهترین پاسخ شناخته شد',
            routeName: 'expert.show',
            routeParameters: ['uuid' => $this->question->uuid],
        )];
    }
}
