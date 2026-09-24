<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Monetization\Domain\TeamSeat;
use App\Support\Audit\AuditEntry;

/**
 * صندلی تیمی پس گرفته شد.
 *
 * ردیف صندلی پاک نمی‌شود؛ فقط `revoked_at` پر می‌شود تا تاریخچه بماند.
 */
final readonly class TeamSeatRevoked implements AuditableEvent
{
    public function __construct(
        public TeamSeat $seat,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'monetization.team_seat_revoked',
            subjectType: TeamSeat::class,
            subjectId: $this->seat->id,
            actorId: $this->actorId,
            after: [
                'subscription_id' => $this->seat->subscription_id,
                'member_user_id' => $this->seat->member_user_id,
                'revoked_at' => $this->seat->revoked_at?->toIso8601String(),
            ],
        );
    }
}
