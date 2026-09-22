<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Ledger\LedgerReceipt;
use App\Support\Ledger\LedgerTransactionRequest;
use InvalidArgumentException;

/**
 * ثبت یک تراکنش دوطرفه در دفتر کل، بدون دانستن اینکه دفتر کل کجا و چطور
 * پیاده شده.
 *
 * هر ماژولی که پول جابه‌جا می‌کند (کمیسیون، شارژ کیف پول، امانت پروژه) از
 * همین یک در عبور می‌کند؛ نوشتن ردیف‌ها، ناوردای صفرشدن و به‌روزرسانی کش
 * موجودی کیف پول همه مسئولیت ماژول Ledger است و پشت این قرارداد پنهان
 * می‌ماند (قاعده ۱).
 *
 * اگر ماژول Ledger غیرفعال باشد، این قرارداد بسته نمی‌شود؛ مصرف‌کننده باید
 * قبل از فراخوانی با `app()->bound()` بررسی کند و بدون آن ادامه دهد — دقیقاً
 * مثل بقیه مرزهای بین‌ماژولی پروژه.
 */
interface LedgerRecorder
{
    /**
     * @throws InvalidArgumentException اگر ردیف‌ها خالی باشند یا جمعشان صفر نشود
     */
    public function record(LedgerTransactionRequest $request): LedgerReceipt;
}
