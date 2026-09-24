<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Actions;

use App\Contracts\FinancialGuard;
use App\Contracts\PaymentGateway;
use App\Modules\Commerce\Domain\Enums\OrderStatus;
use App\Modules\Commerce\Domain\Order;
use App\Support\Payments\PaymentGatewayUnavailable;
use App\Support\Payments\PaymentRequest;
use App\Support\Payments\PaymentRequestResult;
use RuntimeException;

final readonly class StartCheckout
{
    public function __construct(
        private PaymentGateway $gateway,
        private FinancialGuard $guard,
    ) {}

    public function handle(Order $order, ?string $payerMobile = null, ?string $payerEmail = null): PaymentRequestResult
    {
        $this->guard->assertAllowed();

        if ($order->status !== OrderStatus::Pending) {
            throw new RuntimeException('فقط سفارش در انتظار پرداخت به درگاه فرستاده می‌شود.');
        }

        try {
            $result = $this->gateway->requestPayment(new PaymentRequest(
                amount: $order->total(),
                description: 'سفارش '.$order->uuid,
                callbackUrl: route('commerce.callback'),
                orderUuid: $order->uuid,
                payerMobile: $payerMobile,
                payerEmail: $payerEmail,
            ));
        } catch (PaymentGatewayUnavailable $exception) {
            // سفارشی که هرگز به درگاه نرسید، در انتظار پرداخت نمی‌ماند.
            $order->forceFill(['status' => OrderStatus::Failed])->save();

            throw $exception;
        }

        $order->forceFill(['gateway_authority' => $result->authority])->save();

        return $result;
    }
}
