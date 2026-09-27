<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Services;

use App\Models\User;
use App\Modules\Monetization\Domain\Subscription;
use App\Modules\Monetization\Domain\Team;
use App\Modules\Monetization\Domain\TeamSeat;
use Illuminate\Support\Carbon;

/**
 * آیا این کاربر همین حالا دسترسی Pro دارد.
 *
 * سه منبع، یک پاسخ: اشتراک خودش، تیم جاری‌ای که صاحبش است (بخش ۱۹-۶)، یا
 * صندلی فعالی روی اشتراک یا تیم جاری کس دیگر. صندلی از روز اول این‌جاست — اضافه‌کردنش بعداً یعنی بازنویسی
 * همین سرویس و هر چیزی که به آن تکیه کرده.
 */
final readonly class SubscriptionReader
{
    public function hasAccess(User $user, ?Carbon $at = null): bool
    {
        $at ??= Carbon::now();

        return $this->ownSubscription($user, $at) !== null
            || $this->ownsCurrentTeam($user, $at)
            || $this->hasSeat($user, $at);
    }

    public function ownSubscription(User $user, ?Carbon $at = null): ?Subscription
    {
        return Subscription::query()
            ->where('user_id', $user->getKey())
            ->current($at)
            ->first();
    }

    private function ownsCurrentTeam(User $user, Carbon $at): bool
    {
        return Team::query()->where('owner_id', $user->getKey())->current($at)->exists();
    }

    private function hasSeat(User $user, Carbon $at): bool
    {
        return TeamSeat::query()
            ->active()
            ->where('member_user_id', $user->getKey())
            ->where(fn ($query) => $query
                ->whereHas('subscription', fn ($subscription) => $subscription->current($at))
                ->orWhereHas('team', fn ($team) => $team->current($at)))
            ->exists();
    }
}
