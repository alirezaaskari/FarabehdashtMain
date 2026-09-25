<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Actions;

use App\Contracts\FinancialGuard;
use App\Contracts\PaymentGateway;
use App\Models\User;
use App\Modules\Monetization\Domain\Enums\PeriodStatus;
use App\Modules\Monetization\Domain\Plan;
use App\Support\Payments\PaymentGatewayUnavailable;
use App\Support\Payments\PaymentRequest;
use App\Support\Payments\PaymentRequestResult;

/**
 * شروع خرید یا تمدید اشتراک.
 *
 * دوره «در انتظار پرداخت» را {@see OpenSubscriptionPeriod} می‌سازد و این
 * کلاس آن را به درگاه می‌فرستد.
 */
final readonly class StartSubscriptionCheckout
{
    public function __construct(
        private PaymentGateway $gateway,
        private OpenSubscriptionPeriod $openPeriod,
        private FinancialGuard $guard,
    ) {}

    public function handle(User $user, Plan $plan, ?string $payerMobile = null): PaymentRequestResult
    {
        $this->guard->assertAllowed();

        $period = $this->openPeriod->handle($user, $plan);

        try {
            $result = $this->gateway->requestPayment(new PaymentRequest(
                amount: $period->price(),
                description: 'اشتراک '.$plan->title,
                callbackUrl: route('monetization.callback'),
                orderUuid: $period->uuid,
                payerMobile: $payerMobile,
            ));
        } catch (PaymentGatewayUnavailable $exception) {
            // دوره اشتراکی که هرگز به درگاه نرسید، در انتظار پرداخت نمی‌ماند.
            $period->forceFill(['status' => PeriodStatus::Failed])->save();

            throw $exception;
        }

        $period->forceFill(['gateway_authority' => $result->authority])->save();

        return $result;
    }
}
