<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Actions;

use App\Modules\Monetization\Domain\TeamSeat;
use App\Modules\Monetization\Events\TeamSeatRevoked;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;

/**
 * پس‌گرفتن یک صندلی تیمی.
 *
 * ردیف پاک نمی‌شود و داده عضو دست نمی‌خورد: صندلی فقط دسترسی Pro را داده
 * بود، نه مالکیت محاسبه‌ها و پروژه‌های او.
 */
final readonly class RevokeTeamSeat
{
    public function __construct(private Dispatcher $events) {}

    public function handle(TeamSeat $seat, ?int $actorId = null): TeamSeat
    {
        if (! $seat->isActive()) {
            return $seat;
        }

        $seat->forceFill(['revoked_at' => Carbon::now()])->save();

        $this->events->dispatch(new TeamSeatRevoked($seat, $actorId));

        return $seat;
    }
}
