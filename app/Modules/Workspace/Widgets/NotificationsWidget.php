<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Widgets;

use App\Contracts\WorkspaceWidgetSource;
use App\Models\User;
use App\Modules\Workspace\Domain\UserNotification;
use App\Modules\Workspace\Services\NotificationInbox;
use App\Support\JalaliDate;
use App\Support\PersianDigits;
use App\Support\Workspace\WidgetRow;
use App\Support\Workspace\WidgetStat;
use App\Support\Workspace\WorkspaceView;
use App\Support\Workspace\WorkspaceWidget;

/**
 * تازه‌ترین اعلان‌ها — در همه نماها، چون اعلان به حساب تعلق دارد نه به نقش.
 */
final readonly class NotificationsWidget implements WorkspaceWidgetSource
{
    public function __construct(
        private NotificationInbox $inbox,
        private int $limit,
    ) {}

    public function widgets(User $user, WorkspaceView $view): array
    {
        $userId = (int) $user->getKey();

        return [new WorkspaceWidget(
            key: 'notifications',
            title: 'اعلان‌ها',
            order: 5,
            stats: [new WidgetStat('خوانده‌نشده', PersianDigits::from($this->inbox->unreadCount($userId)))],
            rows: $this->inbox->latest($userId, $this->limit)
                ->map(fn (UserNotification $notification): WidgetRow => new WidgetRow(
                    label: $notification->title,
                    url: route('workspace.notifications.open', $notification->uuid),
                    meta: JalaliDate::short($notification->created_at),
                ))
                ->values()
                ->all(),
            empty: 'هنوز اعلانی ندارید. تأیید محصول، دوره و هر جابه‌جایی کیف پول این‌جا خبر داده می‌شود.',
            actionUrl: route('workspace.notifications'),
            actionLabel: 'همه اعلان‌ها',
        )];
    }
}
