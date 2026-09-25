<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Http\Controllers;

use App\Modules\Workspace\Actions\MarkNotificationsRead;
use App\Modules\Workspace\Actions\QueueNoticeSms;
use App\Modules\Workspace\Actions\UpdateSmsPreferences;
use App\Modules\Workspace\Domain\Enums\SmsTopic;
use App\Modules\Workspace\Domain\SmsPreference;
use App\Modules\Workspace\Services\NotificationInbox;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * صفحه اعلان‌ها و تنظیم پیامک آن‌ها.
 *
 * باز کردن یک اعلان از مسیر خود این کنترلر می‌گذرد تا پیش از رفتن به مقصد
 * خوانده‌شده علامت بخورد؛ اعلانی که مقصدش دیگر نیست به همین فهرست برمی‌گردد.
 */
final readonly class NotificationController
{
    public function __construct(
        private NotificationInbox $inbox,
        private MarkNotificationsRead $mark,
        private QueueNoticeSms $sms,
    ) {}

    public function index(Request $request): View
    {
        $userId = (int) $request->user()?->getKey();

        return view('workspace::notifications', [
            'notifications' => $this->inbox->page($userId, (int) config('workspace.notifications.per_page', 20)),
            'unread' => $this->inbox->unreadCount($userId),
            'smsTopics' => $this->sms->enabled() ? $this->smsTopics($userId) : null,
            'smsDailyLimit' => (int) config('workspace.sms.daily_limit', 3),
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

    public function sms(Request $request, UpdateSmsPreferences $update): RedirectResponse
    {
        // کلیدها گروه‌های روشن‌اند (`topics[review]=1`)؛ کلید ناشناخته نادیده می‌ماند.
        $request->validate(['topics' => ['array']]);

        $enabled = array_values(array_filter(array_map(
            static fn (int|string $key): ?SmsTopic => SmsTopic::tryFrom((string) $key),
            array_keys((array) $request->input('topics', [])),
        )));

        $update->handle((int) $request->user()?->getKey(), $enabled);

        return redirect()->route('workspace.notifications')->with('status', 'تنظیم پیامک ذخیره شد.');
    }

    /**
     * @return list<array{topic: SmsTopic, on: bool}>
     */
    private function smsTopics(int $userId): array
    {
        $preference = SmsPreference::query()->where('user_id', $userId)->first();

        return array_map(
            static fn (SmsTopic $topic): array => ['topic' => $topic, 'on' => ! ($preference?->mutes($topic) ?? false)],
            SmsTopic::cases(),
        );
    }
}
