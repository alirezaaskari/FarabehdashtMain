<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\LinkableContentChanged;
use App\Contracts\Revisable;
use App\Contracts\RevisionEvent;
use App\Modules\Chemicals\Domain\ExposureLimit;
use App\Modules\Chemicals\Domain\Substance;
use App\Support\Audit\AuditEntry;

/**
 * ماده‌ای منتشر شد.
 *
 * حد مواجهه عددی است که کارشناس بر اساسش تصمیم می‌گیرد کارگری در معرض خطر
 * هست یا نه. اگر روزی معلوم شود عددی اشتباه بوده، اولین پرسش این است که چه
 * کسی و کِی منتشرش کرد و به کدام منبع استناد کرده بود.
 *
 * دفتر رویداد شناسه و خط استناد می‌نویسد، نه داده شخصی.
 */
final readonly class SubstancePublished implements AuditableEvent, LinkableContentChanged, RevisionEvent
{
    public function __construct(
        public Substance $substance,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'chemicals.substance_published',
            subjectType: Substance::class,
            subjectId: $this->substance->uuid,
            actorId: $this->actorId,
            after: [
                'status' => $this->substance->status->value,
                'cas_number' => $this->substance->cas_number,
                'limits' => $this->substance->limits
                    ->map(static fn (ExposureLimit $limit): string => sprintf(
                        '%s %s %s %s · %s',
                        $limit->authority->value,
                        $limit->type->value,
                        $limit->formattedValue(),
                        $limit->unit,
                        $limit->referenceLine() ?? 'بدون منبع',
                    ))->all(),
            ],
            context: ['slug' => $this->substance->slug, 'name_fa' => $this->substance->name_fa],
        );
    }

    public function affectsPublicLinks(): bool
    {
        return true;
    }

    public function revisable(): Revisable
    {
        return $this->substance;
    }

    public function revisionReason(): string
    {
        return 'انتشار';
    }

    public function revisionAuthorId(): ?int
    {
        return $this->actorId;
    }
}
