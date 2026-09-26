<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Events;

use App\Contracts\UserNotifiableEvent;
use App\Modules\Webinars\Domain\Webinar;
use App\Support\Notifications\UserNotice;

final readonly class WebinarCancelled implements UserNotifiableEvent
{
    /** @param  list<int>  $registrantIds */
    public function __construct(
        public Webinar $webinar,
        public array $registrantIds,
    ) {}

    public function userNotices(): array
    {
        return array_map(fn (int $userId): UserNotice => new UserNotice(
            recipientId: $userId,
            kind: 'webinars.cancelled',
            title: sprintf('رویداد «%s» لغو شد', $this->webinar->title),
            body: $this->webinar->isFree() ? null : 'مبلغ ثبت‌نام به کیف پول شما برگشت.',
            routeName: 'webinars.index',
        ), $this->registrantIds);
    }
}
