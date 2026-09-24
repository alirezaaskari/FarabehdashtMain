<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Payments\FinancialActionBlocked;

/**
 * آیا این نشست اجازه عملیات مالی دارد.
 *
 * قاعده محصول: در حالت «مشاهده به‌عنوان کاربر» هیچ عملیات مالی انجام نمی‌شود.
 * مدیری که با حساب کاربر پول جابه‌جا کند، ردش گم می‌شود.
 *
 * پیش‌فرض `AlwaysAllowed` در `AppServiceProvider` است (بدون ماژول Admin
 * مشاهده‌ای وجود ندارد) و ماژول Admin بازنویسی‌اش می‌کند. دو جا می‌پرسند:
 * میان‌افزار `financial` روی مسیرهای پرداخت، پیش از ساختن هر سفارش؛ و خود
 * Actionهایی که به درگاه درخواست می‌فرستند، برای هر فراخوان دیگری.
 */
interface FinancialGuard
{
    /** @throws FinancialActionBlocked */
    public function assertAllowed(): void;
}
