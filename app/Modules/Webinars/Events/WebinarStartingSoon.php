<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Events;

use App\Contracts\UserNotifiableEvent;
use App\Modules\Webinars\Domain\WebinarRegistration;
use App\Support\JalaliDate;
use App\Support\Notifications\UserNotice;

/** یادآور شروع؛ پیوند جلسه در اعلان و پیامک نمی‌آید، فقط صفحه رویداد. */
final readonly class WebinarStartingSoon implements UserNotifiableEvent
{
    public function __construct(public WebinarRegistration $registration) {}

    public function userNotices(): array
    {
        $webinar = $this->registration->webinar;

        return [new UserNotice(
            recipientId: $this->registration->user_id,
            kind: 'webinars.starting_soon',
            title: sprintf('«%s» به‌زودی شروع می‌شود', $webinar->title),
            body: sprintf('شروع: %s. برای ورود به صفحه رویداد در فرابهداشت بروید.', JalaliDate::longWithTime($webinar->starts_at)),
            routeName: 'webinars.show',
            routeParameters: ['webinar' => $webinar->slug],
        )];
    }
}
