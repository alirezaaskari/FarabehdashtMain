<?php

declare(strict_types=1);

namespace App\Support\Payments;

use App\Contracts\PaymentGateway;
use App\Support\Money;

/**
 * درخواست شروع یک پرداخت — ورودی {@see PaymentGateway::requestPayment()}.
 */
final readonly class PaymentRequest
{
    public function __construct(
        public Money $amount,
        public string $description,
        public string $callbackUrl,
        public string $orderUuid,
        public ?string $payerMobile = null,
        public ?string $payerEmail = null,
    ) {}
}
