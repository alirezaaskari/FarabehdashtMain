<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\LinkableContentChanged;
use App\Contracts\Revisable;
use App\Contracts\RevisionEvent;
use App\Modules\Chemicals\Domain\Enums\SubstanceStatus;
use App\Modules\Chemicals\Domain\Substance;
use App\Support\Audit\AuditEntry;

/**
 * ماده‌ای از ویرایشگر پنل ساخته یا ویرایش شد.
 *
 * ویرایش حد مواجهه ماده منتشرشده بلافاصله روی سایت است؛ نسخه قبلی باید
 * بماند تا بشود گفت کارشناسی که دیروز استناد کرد، چه عددی دیده بود.
 */
final readonly class SubstanceSaved implements AuditableEvent, LinkableContentChanged, RevisionEvent
{
    public function __construct(
        public Substance $substance,
        public ?int $actorId,
        public bool $created,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: $this->created ? 'chemicals.substance_created' : 'chemicals.substance_updated',
            subjectType: Substance::class,
            subjectId: $this->substance->uuid,
            actorId: $this->actorId,
            after: [
                'status' => $this->substance->status->value,
                'cas_number' => $this->substance->cas_number,
                'limits' => $this->substance->limits->count(),
            ],
            context: ['slug' => $this->substance->slug, 'name_fa' => $this->substance->name_fa],
        );
    }

    public function affectsPublicLinks(): bool
    {
        return $this->substance->status === SubstanceStatus::Published;
    }

    public function revisable(): Revisable
    {
        return $this->substance;
    }

    public function revisionReason(): string
    {
        return $this->created ? 'ساخت از پنل' : 'ویرایش از پنل';
    }

    public function revisionAuthorId(): ?int
    {
        return $this->actorId;
    }
}
