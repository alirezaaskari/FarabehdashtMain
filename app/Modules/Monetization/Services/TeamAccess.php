<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Services;

use App\Models\User;
use App\Modules\Monetization\Domain\Team;
use App\Modules\Monetization\Domain\TeamSeat;

/** تیمی که کاربر صاحبش است یا در آن صندلی فعال دارد. */
final readonly class TeamAccess
{
    public function owned(User $user): ?Team
    {
        return Team::query()->where('owner_id', $user->getKey())->first();
    }

    public function seat(User $user): ?TeamSeat
    {
        return TeamSeat::query()
            ->active()
            ->where('member_user_id', $user->getKey())
            ->whereNotNull('team_id')
            ->with('team')
            ->latest('granted_at')
            ->first();
    }

    /**
     * تیمی که کتابخانه‌اش برای این کاربر باز است: تیم خودش (حتی پس از پایان،
     * تا فایل‌هایش را بردارد) یا تیم جاری‌ای که در آن عضو است.
     */
    public function libraryTeam(User $user): ?Team
    {
        $owned = $this->owned($user);

        if ($owned !== null && $owned->ends_at !== null) {
            return $owned;
        }

        $team = $this->seat($user)?->team;

        return $team !== null && $team->isCurrent() ? $team : null;
    }
}
