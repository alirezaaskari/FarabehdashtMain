<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Actions;

use App\Modules\Monetization\Domain\Enums\SubscriptionStatus;
use App\Modules\Monetization\Domain\Subscription;
use App\Modules\Monetization\Events\SubscriptionCancelled;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;

/**
 * توقف تمدید خودکار.
 *
 * `ends_at` دست نمی‌خورد: کاربری که پول این ماه را داده تا پایان همین ماه
 * مشترک است. لغو یعنی «دوره بعدی نیاید»، نه «همین حالا قطع کن».
 */
final readonly class CancelSubscription
{
    public function __construct(private Dispatcher $events) {}

    public function handle(Subscription $subscription, ?int $actorId = null): Subscription
    {
        if ($subscription->status !== SubscriptionStatus::Active) {
            return $subscription;
        }

        $subscription->forceFill([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => Carbon::now(),
        ])->save();

        $this->events->dispatch(new SubscriptionCancelled($subscription, $actorId));

        return $subscription;
    }
}
