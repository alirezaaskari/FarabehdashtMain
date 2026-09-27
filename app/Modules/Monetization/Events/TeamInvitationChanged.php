<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Monetization\Domain\TeamInvitation;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * هر گام دعوت به تیم. دفتر رویداد شماره موبایل دعوت‌شده را نمی‌گیرد، فقط
 * شناسه دعوت و تیم را (قاعده ۷).
 */
final readonly class TeamInvitationChanged implements AuditableEvent, UserNotifiableEvent
{
    public const INVITED = 'invited';

    public const ACCEPTED = 'accepted';

    public const DECLINED = 'declined';

    public const CANCELLED = 'cancelled';

    /** @param  int|null  $inviteeId  حساب صاحب شماره، اگر از قبل ثبت‌نام کرده باشد */
    public function __construct(
        public TeamInvitation $invitation,
        public string $step,
        public ?int $actorId,
        public ?int $inviteeId = null,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'monetization.team_invitation_'.$this->step,
            subjectType: TeamInvitation::class,
            subjectId: $this->invitation->uuid,
            actorId: $this->actorId,
            after: ['status' => $this->invitation->status->value],
            context: ['team_id' => $this->invitation->team_id],
        );
    }

    public function userNotices(): array
    {
        $team = $this->invitation->team->name;
        $owner = $this->invitation->team->owner_id;

        $notice = match ($this->step) {
            self::INVITED => $this->inviteeId === null ? null
                : [$this->inviteeId, 'دعوت به تیم «'.$team.'»', 'با پذیرفتن دعوت، امکانات حرفه‌ای تا پایان اشتراک تیم برای شما باز می‌شود.'],
            self::ACCEPTED => [$owner, 'دعوت تیم پذیرفته شد', 'یک عضو به تیم «'.$team.'» پیوست.'],
            self::DECLINED => [$owner, 'دعوت تیم رد شد', 'صندلی آن دعوت در تیم «'.$team.'» دوباره آزاد است.'],
            default => null,
        };

        return $notice === null ? [] : [new UserNotice(
            recipientId: $notice[0],
            kind: 'monetization.team_invitation_'.$this->step,
            title: $notice[1],
            body: $notice[2],
            routeName: 'monetization.team',
        )];
    }
}
