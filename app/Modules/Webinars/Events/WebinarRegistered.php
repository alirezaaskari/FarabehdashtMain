<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Webinars\Domain\WebinarRegistration;
use App\Support\Audit\AuditEntry;
use App\Support\JalaliDate;
use App\Support\Notifications\UserNotice;

final readonly class WebinarRegistered implements AuditableEvent, UserNotifiableEvent
{
    public function __construct(public WebinarRegistration $registration) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'webinars.registered',
            subjectType: WebinarRegistration::class,
            subjectId: $this->registration->uuid,
            actorId: $this->registration->user_id,
            after: [
                'price_toman' => $this->registration->price_toman,
                'payment_source' => $this->registration->payment_source->value,
            ],
            context: ['webinar_id' => $this->registration->webinar_id],
        );
    }

    public function userNotices(): array
    {
        $webinar = $this->registration->webinar;

        return [new UserNotice(
            recipientId: $this->registration->user_id,
            kind: 'webinars.registered',
            title: sprintf('ثبت‌نام شما در «%s» قطعی شد', $webinar->title),
            body: sprintf('زمان شروع: %s. پیوند ورود از یک ساعت پیش از شروع در صفحه رویداد باز می‌شود.', JalaliDate::longWithTime($webinar->starts_at)),
            routeName: 'webinars.show',
            routeParameters: ['webinar' => $webinar->slug],
        )];
    }
}
