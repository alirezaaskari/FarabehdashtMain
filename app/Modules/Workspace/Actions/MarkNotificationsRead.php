<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Actions;

use App\Modules\Workspace\Domain\UserNotification;
use Illuminate\Support\Carbon;

/**
 * خوانده‌شدن اعلان‌ها — یکی یا همه.
 *
 * همیشه به شناسه کاربر محدود است: شناسه اعلان کس دیگری کاری نمی‌کند.
 */
final readonly class MarkNotificationsRead
{
    public function one(int $userId, string $uuid): ?UserNotification
    {
        $notification = UserNotification::query()
            ->where('user_id', $userId)
            ->where('uuid', $uuid)
            ->first();

        if ($notification !== null && ! $notification->isRead()) {
            $notification->forceFill(['read_at' => Carbon::now()])->save();
        }

        return $notification;
    }

    public function all(int $userId): int
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->unread()
            ->update(['read_at' => Carbon::now()]);
    }
}
