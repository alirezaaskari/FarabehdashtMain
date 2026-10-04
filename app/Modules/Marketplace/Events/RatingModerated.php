<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Marketplace\Domain\MarketRating;
use App\Support\Audit\AuditEntry;

/** مدیر متن یک امتیاز را پنهان یا دوباره آشکار کرد؛ عدد امتیاز دست نمی‌خورد. */
final readonly class RatingModerated implements AuditableEvent
{
    public function __construct(
        public MarketRating $rating,
        public int $adminId,
        public bool $hidden,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: $this->hidden ? 'marketplace.rating_hidden' : 'marketplace.rating_shown',
            subjectType: MarketRating::class,
            subjectId: (string) $this->rating->id,
            actorId: $this->adminId,
        );
    }
}
