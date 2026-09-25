<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\LinkableContentChanged;
use App\Contracts\Revisable;
use App\Contracts\RevisionEvent;
use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use App\Support\Audit\AuditEntry;

/**
 * محتوایی از ویرایشگر پنل ساخته یا ویرایش شد.
 *
 * هر ذخیره یک نسخه می‌گیرد: ویرایش محتوای منتشرشده بلافاصله روی سایت است و
 * باید بشود دید پیش از آن چه نوشته بود.
 */
final readonly class ArticleSaved implements AuditableEvent, LinkableContentChanged, RevisionEvent
{
    public function __construct(
        public Article $article,
        public ?int $actorId,
        public bool $created,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: $this->created ? 'encyclopedia.article_created' : 'encyclopedia.article_updated',
            subjectType: Article::class,
            subjectId: $this->article->uuid,
            actorId: $this->actorId,
            after: [
                'status' => $this->article->status->value,
                'sections' => $this->article->sections->count(),
                'references' => $this->article->references->count(),
            ],
            context: ['slug' => $this->article->slug, 'type' => $this->article->type->value],
        );
    }

    public function affectsPublicLinks(): bool
    {
        return $this->article->status === ArticleStatus::Published;
    }

    public function revisable(): Revisable
    {
        return $this->article;
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
