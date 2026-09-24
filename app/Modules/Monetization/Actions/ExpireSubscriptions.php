<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Actions;

use App\Modules\Monetization\Domain\Enums\SubscriptionStatus;
use App\Modules\Monetization\Domain\Subscription;
use Illuminate\Support\Carbon;

/**
 * علامت‌زدن اشتراک‌هایی که تاریخشان گذشته.
 *
 * دسترسی از قبل با گذشتن `ends_at` قطع شده؛ این کار فقط وضعیت را با واقعیت
 * هم‌تراز می‌کند تا فهرست مدیر و گزارش‌ها درست باشند. پس اجرا نشدن این
 * دستور هیچ‌وقت به کسی دسترسی اضافه نمی‌دهد — قاعده‌ای که عمداً این‌طور
 * چیده شده تا یک cron خراب، درآمد را نبلعد.
 */
final readonly class ExpireSubscriptions
{
    public function handle(?Carbon $at = null): int
    {
        return Subscription::query()
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Cancelled->value])
            ->where(function ($query) use ($at): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '<=', $at ?? Carbon::now());
            })
            ->update(['status' => SubscriptionStatus::Expired]);
    }
}
