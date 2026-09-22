<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Services;

use App\Contracts\CommissionCalculator;
use App\Modules\Commerce\Domain\CommissionRate;
use App\Support\Commerce\CommissionSplit;
use App\Support\Money;
use Illuminate\Support\Carbon;

/**
 * مرجع یگانه کمیسیون (ADR-0003).
 *
 * نرخ ساری همیشه از `commission_rates` خوانده می‌شود: آخرین ردیفی که
 * `effective_from` آن به امروز یا قبل از آن رسیده. اگر هیچ ردیفی ثبت نشده
 * باشد (نصب تازه، پیش از اولین تنظیم مدیر)، نرخ پیش‌فرض تنظیمات به‌کار
 * می‌رود. نتیجه هرگز بازخوانی نمی‌شود — فراخوان همان لحظه آن را روی رکورد
 * خودش Snapshot می‌کند.
 */
final readonly class CommissionService implements CommissionCalculator
{
    public function split(Money $amount, string $flow): CommissionSplit
    {
        $rateBp = $this->currentRateBp($flow);
        $commission = $amount->percentage($rateBp / 100);

        return new CommissionSplit($rateBp, $commission, $amount->minus($commission));
    }

    public function currentRateBp(string $flow, ?Carbon $at = null): int
    {
        $at ??= Carbon::now();

        $rate = CommissionRate::query()
            ->forFlow($flow)
            ->where('effective_from', '<=', $at->toDateString())
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();

        if ($rate !== null) {
            return $rate->rate_bp;
        }

        return (int) config("commerce.commission.default_rate_bp.{$flow}", 2000);
    }
}
