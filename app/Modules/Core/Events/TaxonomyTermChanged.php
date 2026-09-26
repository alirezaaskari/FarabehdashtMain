<?php

declare(strict_types=1);

namespace App\Modules\Core\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Core\Domain\TaxonomyTerm;
use App\Support\Audit\AuditEntry;

/** ساخت، ویرایش یا حذف یک برچسب دسته‌بندی از پنل. */
final readonly class TaxonomyTermChanged implements AuditableEvent
{
    public function __construct(
        public string $action,
        public TaxonomyTerm $term,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'taxonomy.term_'.$this->action,
            subjectType: TaxonomyTerm::class,
            subjectId: $this->term->id,
            actorId: $this->actorId,
            after: $this->action === 'deleted' ? [] : ['name' => $this->term->name, 'slug' => $this->term->slug],
            context: ['taxonomy' => $this->term->taxonomy],
        );
    }
}
