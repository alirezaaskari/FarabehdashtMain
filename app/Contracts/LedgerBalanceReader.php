<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Ledger\LedgerAccountRef;
use App\Support\Money;

/**
 * خواندن موجودی فعلی یک حساب دفتر کل، بدون دانستن اینکه دفتر کل کجا و چطور
 * پیاده شده.
 *
 * برخلاف کیف پول که موجودی‌اش کش‌شده و فوری در دسترس است، حساب‌هایی مثل
 * بدهی فروشنده (`vendor_payable`) کش ندارند — چون هیچ مسیر پرترافیکی به
 * خواندن فوری‌شان نیاز نداشت (README ماژول Ledger). این قرارداد همان یک
 * نیاز واقعی (تسویه فروشنده باید بداند چقدر بدهکار است) را بدون واردکردن
 * مدل `LedgerAccount` برآورده می‌کند (قاعده ۱).
 *
 * حسابی که هنوز هیچ تراکنشی ندیده، موجودی صفر برمی‌گرداند — نه خطا.
 */
interface LedgerBalanceReader
{
    public function balanceOf(LedgerAccountRef $ref): Money;
}
