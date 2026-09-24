<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Monetization\Domain\Subscription;
use App\Modules\Monetization\Domain\SubscriptionPeriod;
use App\Support\Audit\AuditEntry;
use App\Support\JalaliDate;
use App\Support\Notifications\UserNotice;

/**
 * دوره‌ای پرداخت شد و اشتراک تا تاریخ تازه فعال است.
 *
 * برای خرید اول و تمدید یکی است: از دید دفتر رویداد هر دو «یک دوره پول داده
 * شد و پایان اشتراک جلو رفت» هستند.
 */
final readonly class SubscriptionActivated implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(
        public Subscription $subscription,
        public SubscriptionPeriod $period,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'monetization.subscription_activated',
            subjectType: Subscription::class,
            subjectId: $this->subscription->uuid,
            actorId: $this->subscription->user_id,
            after: [
                'status' => $this->subscription->status->value,
                'ends_at' => $this->subscription->ends_at?->toIso8601String(),
            ],
            context: [
                'period_uuid' => $this->period->uuid,
                'plan_id' => $this->period->plan_id,
                'price_toman' => $this->period->price_toman,
                'billing_cycle' => $this->period->billing_cycle->value,
            ],
        );
    }

    public function userNotices(): array
    {
        $endsAt = $this->subscription->ends_at;

        return [new UserNotice(
            recipientId: $this->subscription->user_id,
            kind: 'monetization.subscription_activated',
            title: 'اشتراک حرفه‌ای شما فعال شد',
            body: $endsAt === null ? null : sprintf('اعتبار تا %s', JalaliDate::long($endsAt)),
            routeName: 'monetization.plans',
        )];
    }
}
