<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Monetization\Domain\Team;
use App\Modules\Monetization\Domain\TeamPeriod;
use App\Support\Audit\AuditEntry;
use App\Support\JalaliDate;
use App\Support\Notifications\UserNotice;

/** دوره تیم پرداخت شد؛ خرید اول و تمدید یکی‌اند. */
final readonly class TeamPaid implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public Team $team,
        public TeamPeriod $period,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'monetization.team_paid',
            subjectType: Team::class,
            subjectId: $this->team->uuid,
            actorId: $this->team->owner_id,
            after: [
                'seat_count' => $this->team->seat_count,
                'ends_at' => $this->team->ends_at?->toIso8601String(),
            ],
            context: [
                'period_uuid' => $this->period->uuid,
                'seats' => $this->period->seats,
                'unit_price_toman' => $this->period->unit_price_toman,
                'price_toman' => $this->period->price_toman,
                'billing_cycle' => $this->period->billing_cycle->value,
                'payment_source' => $this->period->payment_source?->value,
            ],
        );
    }

    public function userNotices(): array
    {
        $endsAt = $this->team->ends_at;

        return [new UserNotice(
            recipientId: $this->team->owner_id,
            kind: 'monetization.team_paid',
            title: 'اشتراک تیم فعال شد',
            body: $endsAt === null ? null : sprintf('اعتبار تا %s؛ اعضا را از صفحه تیم دعوت کنید.', JalaliDate::long($endsAt)),
            routeName: 'monetization.team',
        )];
    }
}
