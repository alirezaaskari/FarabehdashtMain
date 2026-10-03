<?php

declare(strict_types=1);

namespace App\Modules\Expert\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\LinkableContentChanged;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Expert\Domain\ExpertAnswer;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * مدیر پاسخ را تأیید کرد: پرسش‌کننده پاسخ تازه دارد و مشاور خبر تأیید را.
 */
final readonly class AnswerPublished implements AuditableEvent, LinkableContentChanged, UserNotifiableEvent
{
    public function __construct(
        public ExpertAnswer $answer,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'expert.answer_published',
            subjectType: ExpertAnswer::class,
            subjectId: $this->answer->uuid,
            actorId: $this->actorId,
            after: ['status' => $this->answer->status->value],
            context: ['question_id' => $this->answer->question_id],
        );
    }

    public function affectsPublicLinks(): bool
    {
        return $this->answer->question->isPublic();
    }

    /**
     * پرسش تحریریه پرسش‌کننده ندارد؛ آن‌وقت فقط مشاور خبر تأیید را می‌گیرد.
     */
    public function userNotices(): array
    {
        $question = $this->answer->question;
        $parameters = ['uuid' => $question->uuid];
        $notices = [];

        if ($question->user_id !== null) {
            $notices[] = new UserNotice(
                recipientId: $question->user_id,
                kind: 'expert.answer_published',
                title: 'پرسش شما پاسخ تازه دارد',
                routeName: 'expert.show',
                routeParameters: $parameters,
            );
        }

        if ($this->answer->user_id !== null) {
            $notices[] = new UserNotice(
                recipientId: $this->answer->user_id,
                kind: 'expert.answer_approved',
                title: 'پاسخ شما تأیید و منتشر شد',
                routeName: 'expert.show',
                routeParameters: $parameters,
            );
        }

        return $notices;
    }
}
