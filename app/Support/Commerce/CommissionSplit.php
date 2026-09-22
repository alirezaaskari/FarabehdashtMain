<?php

declare(strict_types=1);

namespace App\Support\Commerce;

use App\Support\Money;

/**
 * نتیجه تقسیم یک مبلغ به کمیسیون و سهم فروشنده — Snapshot لحظه محاسبه.
 *
 * فراخوان همین سه مقدار را روی رکورد خودش (مثل `order_items`) ذخیره می‌کند؛
 * نرخ اعمال‌شده هرگز از تنظیمات دوباره خوانده نمی‌شود (ADR-0003، «مرجع
 * یگانه کمیسیون»).
 */
final readonly class CommissionSplit
{
    public function __construct(
        public int $rateBp,
        public Money $commission,
        public Money $vendorAmount,
    ) {}
}
