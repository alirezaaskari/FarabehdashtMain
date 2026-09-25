<?php

declare(strict_types=1);

namespace App\Support\Payments;

use RuntimeException;

/**
 * موجودی کیف پول برای این خرید کافی نیست، یا کیف پول در دسترس نیست.
 *
 * پیامش برای کاربر است؛ کنترلر آن را کنار فرم پرداخت نشان می‌دهد.
 */
final class InsufficientWalletBalance extends RuntimeException
{
    public static function make(): self
    {
        return new self('موجودی کیف پول برای این خرید کافی نیست. از درگاه بانکی پرداخت کنید.');
    }
}
