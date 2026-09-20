<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Encyclopedia\Domain\Article;
use App\Support\Audit\AuditEntry;

/**
 * محتوایی منتشر شد.
 *
 * انتشار یک ادعای عمومی است: از این لحظه، متن با نام یک بازبین علمی بیرون
 * می‌رود. دفتر رویداد باید بگوید چه کسی منتشر کرد و به نام چه بازبینی — همان
 * چیزی که اگر روزی محتوایی اشتباه از آب درآمد، اولین پرسش است.
 *
 * شناسه ثبت می‌شود، نه داده شخصی.
 */
final readonly class ArticlePublished implements AuditableEvent
{
    public function __construct(
        public Article $article,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'encyclopedia.article_published',
            subjectType: Article::class,
            subjectId: $this->article->uuid,
            actorId: $this->actorId,
            after: [
                'status' => $this->article->status->value,
                'reviewer_id' => $this->article->reviewer_id,
                'reviewed_at' => $this->article->reviewed_at?->toIso8601String(),
                'review_due_at' => $this->article->review_due_at?->toIso8601String(),
            ],
            context: ['slug' => $this->article->slug, 'type' => $this->article->type->value],
        );
    }
}
