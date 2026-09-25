<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Actions;

use App\Modules\Monetization\Domain\Subscription;
use App\Modules\Monetization\Events\SubscriptionEndingSoon;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;

/**
 * یادآور «اشتراکتان چند روز دیگر تمام می‌شود»، یک‌بار برای هر تاریخ پایان.
 *
 * `ending_reminded_for` پایانی را نگه می‌دارد که یادآورش رفته؛ اجرای دوباره
 * در همان روز یا روز بعد چیزی نمی‌فرستد، و تمدید که پایان را جلو ببرد
 * یادآور دوره بعد را خودبه‌خود ممکن می‌کند.
 */
final readonly class RemindEndingSubscriptions
{
    public function __construct(private Dispatcher $events) {}

    public function handle(?Carbon $at = null): int
    {
        $at ??= Carbon::now();
        $horizon = $at->copy()->addDays((int) config('monetization.ending_reminder_days', 7));
        $reminded = 0;

        Subscription::query()
            ->current($at)
            ->where('ends_at', '<=', $horizon)
            ->where(static function ($query): void {
                $query->whereNull('ending_reminded_for')->orWhereColumn('ending_reminded_for', '!=', 'ends_at');
            })
            ->lazyById(100)
            ->each(function (Subscription $subscription) use (&$reminded): void {
                $endsAt = $subscription->ends_at;

                if ($endsAt === null) {
                    return;
                }

                $subscription->update(['ending_reminded_for' => $endsAt]);
                $this->events->dispatch(new SubscriptionEndingSoon($subscription, $endsAt));
                $reminded++;
            });

        return $reminded;
    }
}
