<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Actions;

use App\Models\User;
use App\Modules\Monetization\Domain\Enums\PeriodStatus;
use App\Modules\Monetization\Domain\Enums\SubscriptionStatus;
use App\Modules\Monetization\Domain\Plan;
use App\Modules\Monetization\Domain\Subscription;
use App\Modules\Monetization\Domain\SubscriptionPeriod;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;

/**
 * ساخت دوره اشتراک «در انتظار پرداخت»، مشترک بین پرداخت درگاه و کیف پول.
 *
 * قیمت پلن همین‌جا روی دوره Snapshot می‌شود: اگر مدیر بین رفتن کاربر به
 * درگاه و برگشتنش قیمت را عوض کند، مبلغی که تأیید می‌شود باید همانی باشد که
 * کاربر دید. دوره‌های نیمه‌کاره قبلی باطل می‌شوند، وگرنه هر کلیک روی دکمه
 * خرید یک ردیف بی‌صاحب تازه می‌ساخت.
 */
final readonly class OpenSubscriptionPeriod
{
    public function __construct(private DatabaseManager $db) {}

    public function handle(User $user, Plan $plan): SubscriptionPeriod
    {
        return $this->db->transaction(function () use ($user, $plan): SubscriptionPeriod {
            $subscription = Subscription::query()->firstOrCreate(
                ['user_id' => $user->getKey()],
                ['uuid' => (string) Str::uuid7(), 'status' => SubscriptionStatus::Active],
            );

            $subscription->periods()
                ->where('status', PeriodStatus::Pending)
                ->update(['status' => PeriodStatus::Failed]);

            return SubscriptionPeriod::query()->create([
                'uuid' => (string) Str::uuid7(),
                'subscription_id' => $subscription->getKey(),
                'plan_id' => $plan->getKey(),
                'billing_cycle' => $plan->billing_cycle,
                'price_toman' => $plan->price_toman,
                'status' => PeriodStatus::Pending,
            ]);
        });
    }
}
