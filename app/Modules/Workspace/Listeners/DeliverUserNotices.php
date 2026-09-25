<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Listeners;

use App\Contracts\UserNotifiableEvent;
use App\Modules\Workspace\Actions\QueueNoticeSms;
use App\Modules\Workspace\Domain\UserNotification;
use Illuminate\Support\Str;

/**
 * هر رویداد {@see UserNotifiableEvent} را به ردیف‌های اعلان تبدیل می‌کند.
 *
 * روی خود قرارداد ثبت می‌شود، نه تک‌تک رویدادها — همان الگوی دفتر رویداد:
 * رویداد تازه برای رسیدن به اعلان‌ها هیچ سیم‌کشی اضافه‌ای لازم ندارد.
 * اعلان مهم علاوه بر صندوق، در صف پیامک هم می‌رود (DEC-39).
 */
final readonly class DeliverUserNotices
{
    public function __construct(private QueueNoticeSms $sms) {}

    public function handle(UserNotifiableEvent $event): void
    {
        foreach ($event->userNotices() as $notice) {
            $notification = UserNotification::query()->create([
                'uuid' => (string) Str::uuid7(),
                'user_id' => $notice->recipientId,
                'kind' => $notice->kind,
                'title' => $notice->title,
                'body' => $notice->body,
                'route_name' => $notice->routeName,
                'route_parameters' => $notice->routeParameters === [] ? null : $notice->routeParameters,
            ]);

            $this->sms->handle($notification);
        }
    }
}
