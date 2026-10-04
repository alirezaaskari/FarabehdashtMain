<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Marketplace\Domain\MarketInvite;
use App\Modules\Marketplace\Domain\MarketProject;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/** کارفرما مشاور یا آزمایشگاهی را به پروژه‌اش دعوت کرد (DEC-89). */
final readonly class ProviderInvited implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public MarketInvite $invite,
        public int $clientId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'marketplace.provider_invited',
            subjectType: MarketProject::class,
            subjectId: $this->invite->project->uuid,
            actorId: $this->clientId,
            context: ['provider_id' => $this->invite->provider_user_id],
        );
    }

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->invite->provider_user_id,
            kind: 'marketplace.invited',
            title: 'دعوت به پیشنهاد برای «'.$this->invite->project->title.'»',
            routeName: 'market.show',
            routeParameters: ['project' => $this->invite->project_id],
        )];
    }
}
