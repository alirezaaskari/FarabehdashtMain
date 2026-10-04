<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Marketplace\Domain\MarketBid;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/** مجری پیشنهادش را پیش از انتخاب کارفرما پس گرفت. */
final readonly class BidWithdrawn implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(public MarketBid $bid) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'marketplace.bid_withdrawn',
            subjectType: MarketBid::class,
            subjectId: $this->bid->uuid,
            actorId: $this->bid->provider_user_id,
            after: ['status' => $this->bid->status->value],
        );
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->bid->project->client_user_id,
            kind: 'marketplace.bid_withdrawn',
            title: 'یک پیشنهاد «'.$this->bid->project->title.'» پس گرفته شد',
            routeName: 'market.client.show',
            routeParameters: ['uuid' => $this->bid->project->uuid],
        )];
    }
}
