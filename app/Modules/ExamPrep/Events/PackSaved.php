<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Events;

use App\Contracts\AuditableEvent;
use App\Modules\ExamPrep\Domain\ExamPack;
use App\Support\Audit\AuditEntry;

/** بسته ساخته یا ویرایش شد؛ تغییر قیمت در دفتر رویداد می‌ماند. */
final readonly class PackSaved implements AuditableEvent
{
    public function __construct(
        public ExamPack $pack,
        public ?int $priceBefore,
        public int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: $this->priceBefore === null ? 'exam_prep.pack_created' : 'exam_prep.pack_updated',
            subjectType: ExamPack::class,
            subjectId: $this->pack->uuid,
            actorId: $this->actorId,
            before: $this->priceBefore === null ? [] : ['price_toman' => $this->priceBefore],
            after: ['price_toman' => $this->pack->price_toman, 'status' => $this->pack->status->value],
            context: ['slug' => $this->pack->slug],
        );
    }
}
