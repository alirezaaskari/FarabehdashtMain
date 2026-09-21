<?php

declare(strict_types=1);

namespace App\Support\Payments;

/**
 * نتیجه موفق شروع پرداخت: کجا کاربر را بفرستیم و بعداً با کدام شناسه تأیید کنیم.
 */
final readonly class PaymentRequestResult
{
    public function __construct(
        public string $authority,
        public string $redirectUrl,
    ) {}
}
