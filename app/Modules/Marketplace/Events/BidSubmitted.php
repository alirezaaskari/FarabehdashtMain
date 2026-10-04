<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Marketplace\Domain\MarketBid;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/** مجری پیشنهاد داد یا پیشنهادش را ویرایش کرد. */
final readonly class BidSubmitted implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public MarketBid $bid,
        public bool $first,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: $this->first ? 'marketplace.bid_submitted' : 'marketplace.bid_updated',
            subjectType: MarketBid::class,
            subjectId: $this->bid->uuid,
            actorId: $this->bid->provider_user_id,
            after: ['total_toman' => $this->bid->total_toman, 'milestones' => count($this->bid->milestones)],
            context: ['project_id' => $this->bid->project_id],
        );
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->bid->project->client_user_id,
            kind: 'marketplace.bid_received',
            title: ($this->first ? 'پیشنهاد تازه برای «' : 'پیشنهاد ویرایش شد برای «').$this->bid->project->title.'»',
            routeName: 'market.client.show',
            routeParameters: ['uuid' => $this->bid->project->uuid],
        )];
    }
}
