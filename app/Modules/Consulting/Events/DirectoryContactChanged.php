<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Consulting\Domain\DirectoryContact;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;

/**
 * درخواست تماس تازه یا پاسخ آزمایشگاه. دفتر رویداد فقط شناسه‌ها را می‌گیرد؛
 * متن پیام و شماره موبایل در آن نمی‌آیند.
 */
final readonly class DirectoryContactChanged implements AuditableEvent, UserNotifiableEvent
{
    public const REQUESTED = 'requested';

    public const REPLIED = 'replied';

    public function __construct(
        public DirectoryContact $contact,
        public string $step,
        public int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'directory.contact_'.$this->step,
            subjectType: DirectoryContact::class,
            subjectId: $this->contact->uuid,
            actorId: $this->actorId,
            context: ['profile_id' => $this->contact->profile_id, 'share_mobile' => $this->contact->share_mobile],
        );
    }

    public function userNotices(): array
    {
        return [$this->step === self::REQUESTED
            ? new UserNotice(
                recipientId: $this->contact->profile->user_id,
                kind: 'directory.contact_requested',
                title: 'درخواست تماس تازه',
                body: 'کسی از صفحه آزمایشگاه شما درخواست تماس فرستاده است.',
                routeName: 'consulting.contacts.incoming',
            )
            : new UserNotice(
                recipientId: $this->contact->user_id,
                kind: 'directory.contact_replied',
                title: 'پاسخ '.$this->contact->profile->display_name,
                body: 'آزمایشگاه به درخواست تماس شما پاسخ داد.',
                routeName: 'consulting.contacts.mine',
            )];
    }
}
