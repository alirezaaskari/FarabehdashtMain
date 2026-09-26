<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Events;

use App\Contracts\AuditableEvent;
use App\Modules\ExamPrep\Domain\ExamPack;
use App\Support\Audit\AuditEntry;

final readonly class QuestionsImported implements AuditableEvent
{
    public function __construct(
        public ExamPack $pack,
        public int $count,
        public int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'exam_prep.questions_imported',
            subjectType: ExamPack::class,
            subjectId: $this->pack->uuid,
            actorId: $this->actorId,
            after: ['count' => $this->count],
        );
    }
}
