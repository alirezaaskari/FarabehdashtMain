<?php

declare(strict_types=1);

namespace App\Support\Payments;

use App\Support\Money;

/**
 * نتیجه تأیید پرداخت.
 *
 * شکست همیشه یک استثنا نیست — بازگشت از درگاه با «پرداخت لغو شد» یا مبلغ
 * ناسازگار هم شکست است و باید به کاربر با یک پیام معمولی نشان داده شود، نه
 * صفحه خطای ۵۰۰.
 */
final readonly class PaymentVerificationResult
{
    private function __construct(
        public bool $successful,
        public ?string $referenceId,
        public ?Money $amount,
        public ?string $failureReason,
    ) {}

    public static function success(string $referenceId, Money $amount): self
    {
        return new self(true, $referenceId, $amount, null);
    }

    public static function failure(string $reason): self
    {
        return new self(false, null, null, $reason);
    }
}
