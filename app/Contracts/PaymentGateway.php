<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Money;
use App\Support\Payments\PaymentRequest;
use App\Support\Payments\PaymentRequestResult;
use App\Support\Payments\PaymentVerificationResult;
use RuntimeException;

/**
 * درگاه پرداخت — زرین‌پال، پشت این قرارداد (تصمیم مدیر، DEC-10).
 *
 * تنها جایی که ریال دیده می‌شود همین آداپتور است؛ `Money::toRialForGateway()`
 * و `Money::fromGatewayRial()` تنها دو نقطه‌ای‌اند که آن مرز را رد می‌کنند
 * (ADR-0003). هیچ Model، Action یا Service دیگری این متدها را صدا نمی‌زند.
 */
interface PaymentGateway
{
    /**
     * @throws RuntimeException اگر خود درخواست به درگاه شکست بخورد (نه رد پرداخت توسط کاربر)
     */
    public function requestPayment(PaymentRequest $request): PaymentRequestResult;

    /**
     * تأیید پرداخت و مقایسه مبلغ بازگشتی با مبلغ انتظارمی.
     *
     * ناسازگاری مبلغ همیشه شکست است، نه استثنا — تا جریان بالادست بتواند
     * سفارش را «ناموفق» علامت بزند و پیام معمولی نشان دهد.
     */
    public function verify(string $authority, Money $expectedAmount): PaymentVerificationResult;
}
