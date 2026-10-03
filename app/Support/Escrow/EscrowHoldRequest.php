<?php

declare(strict_types=1);

namespace App\Support\Escrow;

use App\Support\Ledger\AccountType;
use App\Support\Money;
use App\Support\Payments\PaymentSource;
use InvalidArgumentException;

/**
 * درخواست نگه‌داشتن پول یک خدمت تا پایان کار.
 *
 * `key` را ماژول فراخوان می‌سازد و برای همان خرید همیشه یکسان است (مثلاً
 * uuid درخواست مشاوره)؛ Callback تکراری درگاه با همان کلید امانت دوم نمی‌سازد.
 *
 * کمیسیون همین حالا و از سرویس یگانه کمیسیون (`CommissionCalculator`) حساب و
 * این‌جا ثبت می‌شود: اگر مدیر وسط کار نرخ را عوض کند، این خرید با نرخ روز
 * خرید تسویه می‌شود.
 *
 * `account` حساب امانت را انتخاب می‌کند: خدمت (پیش‌فرض، بخش ۱۹) یا پروژه
 * (بازار پروژه نسخه ۴). دو جریان با قاعده‌های متفاوت در یک حساب قاطی نمی‌شوند.
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
        public AccountType $account = AccountType::ServiceEscrow,
    ) {
        if (! in_array($account, [AccountType::ServiceEscrow, AccountType::ProjectEscrow], true)) {
            throw new InvalidArgumentException('حساب امانت فقط «امانت وجه خدمت» یا «امانت وجه پروژه» است.');
        }
    }
}
