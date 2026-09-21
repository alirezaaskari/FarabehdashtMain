<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Actions;

use App\Contracts\PaymentGateway;
use App\Modules\Commerce\Domain\Enums\OrderStatus;
use App\Modules\Commerce\Domain\Order;
use App\Support\Payments\PaymentRequest;
use App\Support\Payments\PaymentRequestResult;
use RuntimeException;

final readonly class StartCheckout
{
    public function __construct(private PaymentGateway $gateway) {}

    public function handle(Order $order, ?string $payerMobile = null, ?string $payerEmail = null): PaymentRequestResult
    {
        if ($order->status !== OrderStatus::Pending) {
            throw new RuntimeException('فقط سفارش در انتظار پرداخت به درگاه فرستاده می‌شود.');
        }

        $result = $this->gateway->requestPayment(new PaymentRequest(
            amount: $order->total(),
            description: 'سفارش '.$order->uuid,
            callbackUrl: route('commerce.callback'),
            orderUuid: $order->uuid,
            payerMobile: $payerMobile,
            payerEmail: $payerEmail,
        ));

        $order->forceFill(['gateway_authority' => $result->authority])->save();

        return $result;
    }
}
