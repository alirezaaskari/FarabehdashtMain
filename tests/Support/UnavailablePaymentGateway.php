<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\PaymentGateway;
use App\Support\Money;
use App\Support\Payments\PaymentGatewayUnavailable;
use App\Support\Payments\PaymentRequest;
use App\Support\Payments\PaymentRequestResult;
use App\Support\Payments\PaymentVerificationResult;
use LogicException;

/** درگاهی که هرگز در دسترس نیست؛ مثل کلید پذیرنده خالی روی هاست. */
final class UnavailablePaymentGateway implements PaymentGateway
{
    public function requestPayment(PaymentRequest $request): PaymentRequestResult
    {
        throw new PaymentGatewayUnavailable('شناسه پذیرنده زرین‌پال تنظیم نشده است (ZARINPAL_MERCHANT_ID).');
    }

    public function verify(string $authority, Money $expectedAmount): PaymentVerificationResult
    {
        throw new LogicException('پرداختی که هرگز شروع نشد، تأیید نمی‌شود.');
    }
}
