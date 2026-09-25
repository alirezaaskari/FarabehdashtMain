<?php

declare(strict_types=1);

namespace App\Modules\Expert\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\LinkableContentChanged;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Expert\Domain\ExpertQuestion;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * مدیر پرسش را تأیید کرد؛ حالا مشاوران آن را می‌بینند و اگر عمومی باشد، همه.
 */
final readonly class QuestionPublished implements AuditableEvent, LinkableContentChanged, UserNotifiableEvent
{
    public function __construct(
        public ExpertQuestion $question,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'expert.question_published',
            subjectType: ExpertQuestion::class,
            subjectId: $this->question->uuid,
            actorId: $this->actorId,
            after: ['status' => $this->question->status->value],
        );
    }

    public function affectsPublicLinks(): bool
    {
        return $this->question->isPublic();
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->question->user_id,
            kind: 'expert.question_published',
            title: 'پرسش شما تأیید شد و به دست مشاوران رسید',
            routeName: 'expert.show',
            routeParameters: ['uuid' => $this->question->uuid],
        )];
    }
}
