<?php

declare(strict_types=1);

namespace App\Modules\Expert\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Expert\Domain\ExpertAnswer;
use App\Support\Audit\AuditEntry;

/**
 * مشاور پاسخی فرستاد (یا پاسخ ردشده را اصلاح کرد) و پاسخ به صف مدیر رفت.
 */
final readonly class AnswerSubmitted implements AuditableEvent
{
    public function __construct(public ExpertAnswer $answer) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'expert.answer_submitted',
            subjectType: ExpertAnswer::class,
            subjectId: $this->answer->uuid,
            actorId: $this->answer->user_id,
            after: ['status' => $this->answer->status->value],
            context: ['question_id' => $this->answer->question_id],
        );
    }
}
