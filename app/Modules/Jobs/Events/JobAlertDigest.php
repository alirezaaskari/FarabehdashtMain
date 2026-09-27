<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Events;

use App\Contracts\UserNotifiableEvent;
use App\Support\Notifications\UserNotice;

/**
 * خلاصه روزانه هشدار شغل برای کسی که پیامک هشدار را روشن کرده (DEC-73).
 * ماژول اعلان‌ها این نوع را پیامک هم می‌کند؛ روزی یک بار ساخته می‌شود.
 */
final readonly class JobAlertDigest implements UserNotifiableEvent
{
    public function __construct(
        public int $userId,
        public int $count,
    ) {}

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->userId,
            kind: 'jobs.alert_digest',
            title: $this->count.' آگهی شغلی تازه مناسب شما در فرابهداشت',
            routeName: 'jobs.alerts.index',
        )];
    }
}
