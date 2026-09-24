<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Listeners;

use App\Contracts\UserNotifiableEvent;
use App\Modules\Workspace\Domain\UserNotification;
use Illuminate\Support\Str;

/**
 * هر رویداد {@see UserNotifiableEvent} را به ردیف‌های اعلان تبدیل می‌کند.
 *
 * روی خود قرارداد ثبت می‌شود، نه تک‌تک رویدادها — همان الگوی دفتر رویداد:
 * رویداد تازه برای رسیدن به اعلان‌ها هیچ سیم‌کشی اضافه‌ای لازم ندارد.
 */
final readonly class DeliverUserNotices
{
    public function handle(UserNotifiableEvent $event): void
    {
        foreach ($event->userNotices() as $notice) {
            UserNotification::query()->create([
                'uuid' => (string) Str::uuid7(),
                'user_id' => $notice->recipientId,
                'kind' => $notice->kind,
                'title' => $notice->title,
                'body' => $notice->body,
                'route_name' => $notice->routeName,
                'route_parameters' => $notice->routeParameters === [] ? null : $notice->routeParameters,
            ]);
        }
    }
}
