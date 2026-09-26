<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Bundles;

use App\Contracts\BundleComponentSource;
use App\Modules\Monetization\Domain\Enums\BillingCycle;
use App\Modules\Monetization\Domain\Enums\PeriodStatus;
use App\Modules\Monetization\Domain\Enums\SubscriptionStatus;
use App\Modules\Monetization\Domain\Plan;
use App\Modules\Monetization\Domain\Subscription;
use App\Modules\Monetization\Domain\SubscriptionPeriod;
use App\Modules\Monetization\Services\PlanCatalog;
use App\Support\Bundles\BundleComponent;
use App\Support\Money;
use App\Support\Payments\PaymentSource;
use App\Support\PersianDigits;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * چند ماه اشتراک Pro به‌عنوان جزء بسته راه‌حل. ref تعداد ماه است.
 *
 * قیمت تکی = قیمت پلن ماهانه × تعداد ماه؛ درآمد خود پلتفرم است، پس صاحب ندارد.
 * اعطا یک دوره اشتراک با مبلغ صفر و منبع «بسته» می‌سازد که مثل تمدید، از
 * پایان اشتراک فعلی (یا امروز) شروع می‌شود و روزهای باقی‌مانده را نمی‌سوزاند.
 */
final readonly class ProMonthsComponents implements BundleComponentSource
{
    private const array MONTHS = [1, 3, 6, 12];

    public function __construct(
        private PlanCatalog $plans,
        private DatabaseManager $db,
    ) {}

    public function kind(): string
    {
        return 'pro';
    }

    public function label(): string
    {
        return 'ماه‌های اشتراک حرفه‌ای';
    }

    public function options(): array
    {
        return array_values(array_filter(array_map(
            fn (int $months): ?BundleComponent => $this->find((string) $months),
            self::MONTHS,
        )));
    }

    public function find(string $ref): ?BundleComponent
    {
        $months = (int) $ref;
        $plan = $this->monthlyPlan();

        if ($plan === null || ! in_array($months, self::MONTHS, true)) {
            return null;
        }

        return new BundleComponent(
            kind: $this->kind(),
            ref: (string) $months,
            title: PersianDigits::from($months).' ماه اشتراک حرفه‌ای',
            listPrice: Money::toman($plan->price_toman * $months),
            url: Route::has('monetization.plans') ? route('monetization.plans') : null,
        );
    }

    public function owns(int $userId, string $ref): bool
    {
        return false;
    }

    public function grant(int $userId, string $ref, string $purchaseUuid): void
    {
        $plan = $this->monthlyPlan() ?? throw new RuntimeException('پلن ماهانه حرفه‌ای تعریف نشده است.');
        $months = (int) $ref;

        $this->db->transaction(function () use ($userId, $plan, $months): void {
            $subscription = Subscription::query()->lockForUpdate()->firstOrCreate(
                ['user_id' => $userId],
                ['uuid' => (string) Str::uuid7(), 'status' => SubscriptionStatus::Active],
            );

            $now = Carbon::now();
            $startsAt = $subscription->ends_at !== null && $subscription->ends_at->isAfter($now)
                ? $subscription->ends_at->copy()
                : $now;
            $endsAt = $startsAt->copy()->addMonths($months);

            SubscriptionPeriod::query()->create([
                'uuid' => (string) Str::uuid7(),
                'subscription_id' => $subscription->getKey(),
                'plan_id' => $plan->getKey(),
                'billing_cycle' => BillingCycle::Monthly,
                'price_toman' => 0,
                'status' => PeriodStatus::Paid,
                'payment_source' => PaymentSource::Bundle,
                'paid_at' => $now,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            $subscription->forceFill([
                'plan_id' => $subscription->plan_id ?? $plan->getKey(),
                'status' => SubscriptionStatus::Active,
                'started_at' => $subscription->started_at ?? $startsAt,
                'ends_at' => $endsAt,
                'cancelled_at' => null,
            ])->save();
        });
    }

    private function monthlyPlan(): ?Plan
    {
        return $this->plans->active()->first(static fn (Plan $plan): bool => $plan->billing_cycle === BillingCycle::Monthly);
    }
}
