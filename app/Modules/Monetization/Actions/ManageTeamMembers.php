<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Actions;

use App\Models\User;
use App\Modules\Monetization\Domain\Enums\InvitationStatus;
use App\Modules\Monetization\Domain\Team;
use App\Modules\Monetization\Domain\TeamInvitation;
use App\Modules\Monetization\Domain\TeamSeat;
use App\Modules\Monetization\Events\TeamInvitationChanged;
use App\Modules\Monetization\Events\TeamSeatAssigned;
use App\Support\Mobile;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * دعوت، پذیرش و خروج اعضای تیم (بخش ۱۹-۶).
 *
 * صاحب تیم فقط شماره دعوت می‌کند و صندلی را پس می‌گیرد؛ هیچ مسیری از این
 * کلاس به داده عضو نمی‌رسد. هر کاربر در یک زمان فقط در یک تیم صندلی دارد.
 */
final readonly class ManageTeamMembers
{
    public function __construct(
        private RevokeTeamSeat $revoke,
        private DatabaseManager $db,
        private Dispatcher $events,
    ) {}

    public function invite(User $owner, Team $team, string $mobileInput): TeamInvitation
    {
        $this->assertOwner($owner, $team);

        if (! $team->isCurrent()) {
            throw new RuntimeException('اشتراک تیم تمام شده است؛ اول تمدید کنید.');
        }

        $mobile = (Mobile::tryFromInput($mobileInput) ?? throw new RuntimeException('شماره موبایل معتبر نیست.'))->value;

        if ($mobile === $owner->mobile) {
            throw new RuntimeException('صاحب تیم خودش یک صندلی دارد و دعوت نمی‌خواهد.');
        }

        $invitation = $this->db->transaction(function () use ($owner, $team, $mobile): TeamInvitation {
            Team::query()->whereKey($team->id)->lockForUpdate()->first();

            if ($team->freeSeats() < 1) {
                throw new RuntimeException('صندلی خالی ندارید؛ برای عضو تازه تعداد صندلی را بیشتر کنید.');
            }

            if ($team->invitations()->pending()->where('mobile', $mobile)->exists()) {
                throw new RuntimeException('این شماره دعوت باز دارد.');
            }

            $member = User::query()->where('mobile', $mobile)->first();

            if ($member !== null && $team->seats()->active()->where('member_user_id', $member->id)->exists()) {
                throw new RuntimeException('این شماره همین حالا عضو تیم است.');
            }

            return TeamInvitation::query()->create([
                'uuid' => (string) Str::uuid7(),
                'team_id' => $team->id,
                'mobile' => $mobile,
                'invited_by' => $owner->getKey(),
                'status' => InvitationStatus::Pending,
            ]);
        });

        $inviteeId = User::query()->where('mobile', $mobile)->value('id');
        $this->events->dispatch(new TeamInvitationChanged($invitation, TeamInvitationChanged::INVITED, (int) $owner->getKey(), $inviteeId === null ? null : (int) $inviteeId));

        return $invitation;
    }

    public function cancel(User $owner, TeamInvitation $invitation): void
    {
        $this->assertOwner($owner, $invitation->team);
        $this->close($invitation, InvitationStatus::Cancelled, TeamInvitationChanged::CANCELLED, (int) $owner->getKey());
    }

    public function decline(User $user, TeamInvitation $invitation): void
    {
        $this->assertInvitee($user, $invitation);
        $this->close($invitation, InvitationStatus::Declined, TeamInvitationChanged::DECLINED, (int) $user->getKey());
    }

    public function accept(User $user, TeamInvitation $invitation): TeamSeat
    {
        $this->assertInvitee($user, $invitation);
        $team = $invitation->team;

        if (! $team->isCurrent()) {
            throw new RuntimeException('اشتراک این تیم تمام شده است.');
        }

        if ($team->owner_id === $user->getKey()) {
            throw new RuntimeException('صاحب تیم خودش یک صندلی دارد.');
        }

        $seat = $this->db->transaction(function () use ($user, $invitation, $team): TeamSeat {
            $other = TeamSeat::query()->active()->where('member_user_id', $user->getKey())
                ->whereNotNull('team_id')->where('team_id', '!=', $team->id)
                ->whereHas('team', fn ($query) => $query->current())
                ->exists();

            if ($other) {
                throw new RuntimeException('در تیم دیگری عضوید؛ اول از آن تیم خارج شوید.');
            }

            $invitation->forceFill(['status' => InvitationStatus::Accepted, 'responded_at' => Carbon::now()])->save();

            $seat = TeamSeat::query()->firstOrNew(['team_id' => $team->id, 'member_user_id' => $user->getKey()]);
            $seat->fill(['granted_by' => $invitation->invited_by, 'granted_at' => Carbon::now(), 'revoked_at' => null])->save();

            return $seat;
        });

        $this->events->dispatch(new TeamInvitationChanged($invitation, TeamInvitationChanged::ACCEPTED, (int) $user->getKey()));
        $this->events->dispatch(new TeamSeatAssigned($seat, (int) $user->getKey()));

        return $seat;
    }

    /** صاحب تیم صندلی عضو را پس می‌گیرد؛ داده عضو مال خودش می‌ماند. */
    public function remove(User $owner, Team $team, TeamSeat $seat): void
    {
        $this->assertOwner($owner, $team);

        if ($seat->team_id !== $team->id) {
            throw new RuntimeException('این صندلی مال تیم شما نیست.');
        }

        $this->revoke->handle($seat, (int) $owner->getKey());
    }

    public function leave(User $member, TeamSeat $seat): void
    {
        if ($seat->member_user_id !== $member->getKey() || $seat->team_id === null) {
            throw new RuntimeException('این صندلی مال شما نیست.');
        }

        $this->revoke->handle($seat, (int) $member->getKey());
    }

    private function close(TeamInvitation $invitation, InvitationStatus $status, string $step, int $actorId): void
    {
        if ($invitation->status !== InvitationStatus::Pending) {
            throw new RuntimeException('این دعوت دیگر باز نیست.');
        }

        $invitation->forceFill(['status' => $status, 'responded_at' => Carbon::now()])->save();
        $this->events->dispatch(new TeamInvitationChanged($invitation, $step, $actorId));
    }

    private function assertOwner(User $owner, Team $team): void
    {
        if ($team->owner_id !== $owner->getKey()) {
            throw new RuntimeException('فقط صاحب تیم این کار را می‌کند.');
        }
    }

    private function assertInvitee(User $user, TeamInvitation $invitation): void
    {
        if ($invitation->mobile !== $user->mobile) {
            throw new RuntimeException('این دعوت برای شماره شما نیست.');
        }

        if ($invitation->status !== InvitationStatus::Pending) {
            throw new RuntimeException('این دعوت دیگر باز نیست.');
        }
    }
}
