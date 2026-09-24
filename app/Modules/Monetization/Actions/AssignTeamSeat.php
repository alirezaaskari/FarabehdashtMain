<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Actions;

use App\Models\User;
use App\Modules\Monetization\Domain\Subscription;
use App\Modules\Monetization\Domain\TeamSeat;
use App\Modules\Monetization\Events\TeamSeatAssigned;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * دادن صندلی یک اشتراک به حساب مستقل یک عضو.
 *
 * صندلی باطل‌شده دوباره فعال می‌شود، نه اینکه ردیف دوم بسازد — وگرنه
 * تاریخچه عضو به چند ردیف تکه‌تکه می‌شد.
 */
final readonly class AssignTeamSeat
{
    public function __construct(private Dispatcher $events) {}

    public function handle(Subscription $subscription, User $member, ?int $actorId = null): TeamSeat
    {
        if ($subscription->user_id === $member->getKey()) {
            throw new RuntimeException('صاحب اشتراک به صندلی نیاز ندارد.');
        }

        $seat = TeamSeat::query()->firstOrNew([
            'subscription_id' => $subscription->getKey(),
            'member_user_id' => $member->getKey(),
        ]);

        $seat->fill([
            'granted_by' => $actorId,
            'granted_at' => Carbon::now(),
            'revoked_at' => null,
        ])->save();

        $this->events->dispatch(new TeamSeatAssigned($seat, $actorId));

        return $seat;
    }
}
