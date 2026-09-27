<?php

declare(strict_types=1);

namespace App\Support\Escrow;

use App\Support\Money;
use App\Support\Payments\PaymentSource;

/**
 * درخواست نگه‌داشتن پول یک خدمت تا پایان کار.
 *
 * `key` را ماژول فراخوان می‌سازد و برای همان خرید همیشه یکسان است (مثلاً
 * uuid درخواست مشاوره)؛ Callback تکراری درگاه با همان کلید امانت دوم نمی‌سازد.
 *
 * کمیسیون همین حالا و از سرویس یگانه کمیسیون (`CommissionCalculator`) حساب و
 * این‌جا ثبت می‌شود: اگر مدیر وسط کار نرخ را عوض کند، این خرید با نرخ روز
 * خرید تسویه می‌شود.
 */
final readonly class EscrowHoldRequest
{
    public function __construct(
        public string $key,
        public int $payerUserId,
        public int $payeeUserId,
        public Money $amount,
        public Money $commission,
        public PaymentSource $source,
        public string $referenceType,
        public string $referenceId,
        public ?string $memo = null,
    ) {}
}
