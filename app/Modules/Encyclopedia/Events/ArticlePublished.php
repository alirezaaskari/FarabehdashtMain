<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\LinkableContentChanged;
use App\Contracts\Revisable;
use App\Contracts\RevisionEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Encyclopedia\Domain\Article;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * محتوایی منتشر شد.
 *
 * انتشار یک ادعای عمومی است: از این لحظه، متن با نام یک بازبین علمی بیرون
 * می‌رود. دفتر رویداد باید بگوید چه کسی منتشر کرد و به نام چه بازبینی — همان
 * چیزی که اگر روزی محتوایی اشتباه از آب درآمد، اولین پرسش است.
 *
 * شناسه ثبت می‌شود، نه داده شخصی.
 */
final readonly class ArticlePublished implements AuditableEvent, LinkableContentChanged, RevisionEvent, UserNotifiableEvent
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

    public function affectsPublicLinks(): bool
    {
        return true;
    }

    public function revisable(): Revisable
    {
        return $this->article;
    }

    public function revisionReason(): string
    {
        return 'انتشار';
    }

    public function revisionAuthorId(): ?int
    {
        return $this->actorId;
    }

    /** نویسنده‌ای که پیش‌نویس را فرستاده باخبر می‌شود؛ مدیری که خودش نوشته، نه. */
    public function userNotices(): array
    {
        $authorId = $this->article->author_id;

        if ($authorId === null || $authorId === $this->actorId) {
            return [];
        }

        return [new UserNotice(
            recipientId: $authorId,
            kind: 'encyclopedia.article_published',
            title: sprintf('نوشته «%s» در دانشنامه منتشر شد', $this->article->title),
            routeName: 'encyclopedia.show',
            routeParameters: ['slug' => $this->article->slug],
        )];
    }
}
