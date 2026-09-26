<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Actions;

use App\Contracts\FinancialGuard;
use App\Contracts\PaymentGateway;
use App\Modules\Webinars\Domain\Enums\RegistrationStatus;
use App\Modules\Webinars\Domain\WebinarRegistration;
use App\Support\Payments\PaymentGatewayUnavailable;
use App\Support\Payments\PaymentRequest;
use App\Support\Payments\PaymentRequestResult;
use RuntimeException;

final readonly class StartRegistrationCheckout
{
    public function __construct(
        private PaymentGateway $gateway,
        private FinancialGuard $guard,
    ) {}

    public function handle(WebinarRegistration $purchase, ?string $payerMobile = null): PaymentRequestResult
    {
        $this->guard->assertAllowed();

        if ($purchase->status !== RegistrationStatus::Pending) {
            throw new RuntimeException('فقط ثبت‌نام در انتظار پرداخت به درگاه فرستاده می‌شود.');
        }

        try {
            $result = $this->gateway->requestPayment(new PaymentRequest(
                amount: $purchase->price(),
                description: 'ثبت‌نام رویداد '.$purchase->uuid,
                callbackUrl: route('webinars.register.callback'),
                orderUuid: $purchase->uuid,
                payerMobile: $payerMobile,
            ));
        } catch (PaymentGatewayUnavailable $exception) {
            $purchase->forceFill(['status' => RegistrationStatus::Failed])->save();

            throw $exception;
        }

        $purchase->forceFill(['gateway_authority' => $result->authority])->save();

        return $result;
    }
}
