<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Encyclopedia\Domain\Article;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * مدیر پیش‌نویس را برای اصلاح به نویسنده برگرداند.
 *
 * متن یادداشت در دفتر رویداد نمی‌آید (ممکن است نام یا نکته شخصی داشته
 * باشد)؛ روی خود محتوا و در اعلان نویسنده هست.
 */
final readonly class ArticleReturnedToWriter implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public Article $article,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'encyclopedia.article_returned',
            subjectType: Article::class,
            subjectId: $this->article->uuid,
            actorId: $this->actorId,
            after: ['status' => $this->article->status->value],
        );
    }

    public function userNotices(): array
    {
        if ($this->article->author_id === null || $this->article->author_id === $this->actorId) {
            return [];
        }

        return [new UserNotice(
            recipientId: $this->article->author_id,
            kind: 'encyclopedia.article_returned',
            title: sprintf('نوشته «%s» برای اصلاح برگشت', $this->article->title),
            body: $this->article->review_note,
            routeName: 'encyclopedia.writing.edit',
            routeParameters: ['uuid' => $this->article->uuid],
        )];
    }
}
