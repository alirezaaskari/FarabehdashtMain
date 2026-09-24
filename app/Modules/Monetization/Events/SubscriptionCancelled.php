<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Monetization\Domain\Subscription;
use App\Support\Audit\AuditEntry;

/**
 * کاربر تمدید خودکار را متوقف کرد.
 *
 * دسترسی همین حالا قطع نمی‌شود؛ `ends_at` دست‌نخورده می‌ماند.
 */
final readonly class SubscriptionCancelled implements AuditableEvent
{
    public function __construct(
        public Subscription $subscription,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'monetization.subscription_cancelled',
            subjectType: Subscription::class,
            subjectId: $this->subscription->uuid,
            actorId: $this->actorId,
            after: [
                'status' => $this->subscription->status->value,
                'ends_at' => $this->subscription->ends_at?->toIso8601String(),
            ],
        );
    }
}
