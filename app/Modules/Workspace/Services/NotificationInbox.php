<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Services;

use App\Modules\Workspace\Domain\UserNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * خواندن اعلان‌های یک کاربر.
 */
final readonly class NotificationInbox
{
    public function unreadCount(int $userId): int
    {
        return UserNotification::query()->where('user_id', $userId)->unread()->count();
    }

    /** @return Collection<int, UserNotification> */
    public function latest(int $userId, int $limit): Collection
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /** @return LengthAwarePaginator<int, UserNotification> */
    public function page(int $userId, int $perPage): LengthAwarePaginator
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->paginate($perPage);
    }
}
