<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Http\Controllers;

use App\Modules\Workspace\Actions\MarkNotificationsRead;
use App\Modules\Workspace\Services\NotificationInbox;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * صفحه اعلان‌ها.
 *
 * باز کردن یک اعلان از مسیر خود این کنترلر می‌گذرد تا پیش از رفتن به مقصد
 * خوانده‌شده علامت بخورد؛ اعلانی که مقصدش دیگر نیست به همین فهرست برمی‌گردد.
 */
final readonly class NotificationController
{
    public function __construct(
        private NotificationInbox $inbox,
        private MarkNotificationsRead $mark,
    ) {}

    public function index(Request $request): View
    {
        $userId = (int) $request->user()?->getKey();

        return view('workspace::notifications', [
            'notifications' => $this->inbox->page($userId, (int) config('workspace.notifications.per_page', 20)),
            'unread' => $this->inbox->unreadCount($userId),
        ]);
    }

    public function open(Request $request, string $uuid): RedirectResponse
    {
        $notification = $this->mark->one((int) $request->user()?->getKey(), $uuid);

        abort_if($notification === null, 404);

        return redirect()->to($notification->url() ?? route('workspace.notifications'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $this->mark->all((int) $request->user()?->getKey());

        return redirect()->route('workspace.notifications')->with('status', 'همه اعلان‌ها خوانده‌شده علامت خوردند.');
    }
}
