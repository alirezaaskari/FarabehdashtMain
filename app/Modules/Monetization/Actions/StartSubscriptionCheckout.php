<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Actions;

use App\Contracts\FinancialGuard;
use App\Contracts\PaymentGateway;
use App\Models\User;
use App\Modules\Monetization\Domain\Enums\PeriodStatus;
use App\Modules\Monetization\Domain\Enums\SubscriptionStatus;
use App\Modules\Monetization\Domain\Plan;
use App\Modules\Monetization\Domain\Subscription;
use App\Modules\Monetization\Domain\SubscriptionPeriod;
use App\Support\Payments\PaymentRequest;
use App\Support\Payments\PaymentRequestResult;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;

/**
 * شروع خرید یا تمدید اشتراک.
 *
 * دوره در وضعیت «در انتظار پرداخت» ساخته می‌شود و قیمت پلن همان‌جا روی آن
 * Snapshot می‌شود: اگر مدیر بین رفتن کاربر به درگاه و برگشتنش قیمت را عوض
 * کند، مبلغی که تأیید می‌شود باید همانی باشد که کاربر دید.
 *
 * دوره‌های نیمه‌کاره قبلی باطل می‌شوند، وگرنه هر بار کلیک روی دکمه خرید یک
 * ردیف بی‌صاحب تازه می‌ساخت.
 */
final readonly class StartSubscriptionCheckout
{
    public function __construct(
        private PaymentGateway $gateway,
        private DatabaseManager $db,
        private FinancialGuard $guard,
    ) {}

    public function handle(User $user, Plan $plan, ?string $payerMobile = null): PaymentRequestResult
    {
        $this->guard->assertAllowed();

        $period = $this->db->transaction(function () use ($user, $plan): SubscriptionPeriod {
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

        $result = $this->gateway->requestPayment(new PaymentRequest(
            amount: $period->price(),
            description: 'اشتراک '.$plan->title,
            callbackUrl: route('monetization.callback'),
            orderUuid: $period->uuid,
            payerMobile: $payerMobile,
        ));

        $period->forceFill(['gateway_authority' => $result->authority])->save();

        return $result;
    }
}
