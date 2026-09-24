<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Monetization\Domain\TeamSeat;
use App\Support\Audit\AuditEntry;

/**
 * صندلی تیمی به حساب یک عضو داده شد.
 *
 * شناسه عضو نوشته می‌شود، نه شماره موبایلش (قاعده ۷).
 */
final readonly class TeamSeatAssigned implements AuditableEvent
{
    public function __construct(
        public TeamSeat $seat,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'monetization.team_seat_assigned',
            subjectType: TeamSeat::class,
            subjectId: $this->seat->id,
            actorId: $this->actorId,
            after: [
                'subscription_id' => $this->seat->subscription_id,
                'member_user_id' => $this->seat->member_user_id,
            ],
        );
    }
}
