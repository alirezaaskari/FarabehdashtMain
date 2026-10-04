<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Marketplace\Domain\MarketContract;
use App\Modules\Marketplace\Domain\MarketRating;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/** یک طرف قرارداد به طرف دیگر امتیاز داد (DEC-85). متن امتیاز در دفتر رویداد نمی‌آید. */
final readonly class ContractRated implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(public MarketRating $rating) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'marketplace.contract_rated',
            subjectType: MarketContract::class,
            subjectId: $this->rating->contract->uuid,
            actorId: $this->rating->rater_user_id,
            context: ['rating_id' => $this->rating->id, 'stars' => $this->rating->stars],
        );
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->rating->ratee_user_id,
            kind: 'marketplace.rated',
            title: 'طرف قرارداد «'.$this->rating->contract->project->title.'» امتیازش را داد',
            body: 'امتیاز او پس از ثبت امتیاز شما یا گذشت مهلت نمایش دیده می‌شود.',
            routeName: 'market.contracts.show',
            routeParameters: ['uuid' => $this->rating->contract->uuid],
        )];
    }
}
