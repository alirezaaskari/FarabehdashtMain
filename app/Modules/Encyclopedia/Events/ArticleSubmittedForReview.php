<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Encyclopedia\Domain\Article;
use App\Support\Audit\AuditEntry;

/** پیش‌نویس به صف بازبینی رفت. */
final readonly class ArticleSubmittedForReview implements AuditableEvent
{
    public function __construct(
        public Article $article,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'encyclopedia.article_submitted',
            subjectType: Article::class,
            subjectId: $this->article->uuid,
            actorId: $this->actorId,
            after: ['status' => $this->article->status->value],
        );
    }
}
