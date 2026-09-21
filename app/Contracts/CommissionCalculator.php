<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Commerce\CommissionSplit;
use App\Support\Money;

/**
 * تقسیم یک مبلغ فروش به کمیسیون پلتفرم و سهم فروشنده.
 *
 * مرجع یگانه کمیسیون (ADR-0003): همه جریان‌های فروش — فروشگاه، دوره‌ها و
 * هر جریان آینده — از همین یک سرویس عبور می‌کنند تا نرخ هرگز دوباره و با
 * منطق پراکنده حساب نشود. ماژول دوره‌ها (بخش ۱۳) این قرارداد را می‌بندد،
 * نه مدل‌های ماژول تجارت را (قاعده ۱).
 *
 * اگر ماژول تجارت غیرفعال باشد، این قرارداد بسته نمی‌شود و مصرف‌کننده باید
 * قبل از فراخوانی با `app()->bound()` بررسی کند.
 */
interface CommissionCalculator
{
    /**
     * @param  string  $flow  شناسه جریان فروش، مثل «shop» یا «course»
     */
    public function split(Money $amount, string $flow): CommissionSplit;
}
