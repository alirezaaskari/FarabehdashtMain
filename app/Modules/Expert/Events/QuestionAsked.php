<?php

declare(strict_types=1);

namespace App\Modules\Expert\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Expert\Domain\ExpertQuestion;
use App\Support\Audit\AuditEntry;

/**
 * پرسش تازه به صف تأیید مدیر رفت. متن پرسش در دفتر رویداد نمی‌رود.
 */
final readonly class QuestionAsked implements AuditableEvent
{
    public function __construct(public ExpertQuestion $question) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'expert.question_asked',
            subjectType: ExpertQuestion::class,
            subjectId: $this->question->uuid,
            actorId: $this->question->user_id,
            after: [
                'status' => $this->question->status->value,
                'visibility' => $this->question->visibility->value,
                'topic' => $this->question->topic->value,
                'priority' => $this->question->priority,
            ],
        );
    }
}
