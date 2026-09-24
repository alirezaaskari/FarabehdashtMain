<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Services;

use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Support\Money;

/**
 * چهار عددی که سند کلیدها می‌خواهد پیش از خاموش‌کردن یک جریان نشان داده شود.
 *
 * `refundNeeded` فقط وقتی معنا دارد که مدیر سیاست «بازگشت وجه نسبت‌به‌مدت»
 * را انتخاب کند؛ همیشه حساب می‌شود تا مدیر پیش از انتخاب بداند چقدر است.
 */
final readonly class ShutdownSummary
{
    public function __construct(
        public RevenueStream $stream,
        public int $activeSubscribers,
        public Money $monthlyRevenue,
        public int $hiddenPages,
        public Money $refundNeeded,
    ) {}
}
