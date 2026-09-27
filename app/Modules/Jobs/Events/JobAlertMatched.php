<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Events;

use App\Contracts\UserNotifiableEvent;
use App\Modules\Jobs\Domain\JobPosting;
use App\Support\Notifications\UserNotice;

/**
 * آگهی تازه با هشدار چند کاربر جور شد؛ هر کدام یک اعلان درون سایت می‌گیرند.
 * پیامک از این اعلان نمی‌رود، فقط از خلاصه روزانه.
 */
final readonly class JobAlertMatched implements UserNotifiableEvent
{
    /** @param  list<int>  $userIds */
    public function __construct(
        public JobPosting $posting,
        public array $userIds,
    ) {}

    public function userNotices(): array
    {
        return array_map(fn (int $userId): UserNotice => new UserNotice(
            recipientId: $userId,
            kind: 'jobs.alert_match',
            title: 'آگهی تازه مناسب شما: '.$this->posting->title,
            body: $this->posting->company->name,
            routeName: 'jobs.show',
            routeParameters: ['posting' => $this->posting->id],
        ), $this->userIds);
    }
}
