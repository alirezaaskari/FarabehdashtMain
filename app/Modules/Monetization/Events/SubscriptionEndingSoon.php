<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Events;

use App\Contracts\UserNotifiableEvent;
use App\Modules\Monetization\Domain\Subscription;
use App\Support\JalaliDate;
use App\Support\Notifications\UserNotice;
use Carbon\CarbonInterface;

/**
 * اشتراک چند روز دیگر تمام می‌شود.
 *
 * داده‌ای عوض نمی‌کند، پس رویداد دفتر رویداد نیست؛ فقط اعلان است.
 */
final readonly class SubscriptionEndingSoon implements UserNotifiableEvent
{
    public function __construct(
        public Subscription $subscription,
        public CarbonInterface $endsAt,
    ) {}

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->subscription->user_id,
            kind: 'monetization.subscription_ending',
            title: 'اشتراک حرفه‌ای شما به‌زودی تمام می‌شود',
            body: sprintf('اعتبار تا %s است. برای ادامه، از صفحه اشتراک تمدید کنید.', JalaliDate::long($this->endsAt)),
            routeName: 'monetization.plans',
        )];
    }
}
