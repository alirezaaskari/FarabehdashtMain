<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Monetization\Domain\TeamSeat;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * صندلی تیمی به حساب یک عضو داده شد.
 *
 * شناسه عضو نوشته می‌شود، نه شماره موبایلش (قاعده ۷).
 */
final readonly class TeamSeatAssigned implements AuditableEvent, UserNotifiableEvent
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

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->seat->member_user_id,
            kind: 'monetization.team_seat_assigned',
            title: 'یک صندلی اشتراک تیمی به شما داده شد',
            body: 'امکانات حرفه‌ای تا پایان اشتراک تیم برای شما باز است.',
            routeName: 'monetization.plans',
        )];
    }
}
