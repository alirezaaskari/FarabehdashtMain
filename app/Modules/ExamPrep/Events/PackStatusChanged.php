<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Events;

use App\Contracts\AuditableEvent;
use App\Modules\ExamPrep\Domain\ExamPack;
use App\Support\Audit\AuditEntry;

/** انتشار یا بایگانی بسته. */
final readonly class PackStatusChanged implements AuditableEvent
{
    public function __construct(
        public ExamPack $pack,
        public string $before,
        public int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'exam_prep.pack_'.$this->pack->status->value,
            subjectType: ExamPack::class,
            subjectId: $this->pack->uuid,
            actorId: $this->actorId,
            before: ['status' => $this->before],
            after: ['status' => $this->pack->status->value],
        );
    }
}
