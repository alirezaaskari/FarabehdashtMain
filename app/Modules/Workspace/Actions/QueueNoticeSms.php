<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Actions;

use App\Contracts\SmsSender;
use App\Modules\Workspace\Domain\Enums\SmsDeliveryStatus;
use App\Modules\Workspace\Domain\Enums\SmsTopic;
use App\Modules\Workspace\Domain\SmsDelivery;
use App\Modules\Workspace\Domain\SmsPreference;
use App\Modules\Workspace\Domain\UserNotification;
use App\Modules\Workspace\Services\SmsWindow;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Carbon;

/**
 * اعلان مهم را در صف پیامک می‌گذارد (DEC-39).
 *
 * این‌جا چیزی فرستاده نمی‌شود: رویداد داخل تراکنش خرید یا تأیید منتشر
 * می‌شود و تماس با سامانه پیامکی نباید آن تراکنش را کند یا خراب کند.
 * {@see SendDueNoticeSms} هر دقیقه صف را خالی می‌کند.
 */
final readonly class QueueNoticeSms
{
    public function __construct(
        private Container $container,
        private SmsWindow $window,
    ) {}

    public function handle(UserNotification $notification): ?SmsDelivery
    {
        if (! $this->enabled()) {
            return null;
        }

        $topic = SmsTopic::forKind($notification->kind);

        if ($topic === null || $this->muted($notification->user_id, $topic)) {
            return null;
        }

        return SmsDelivery::query()->create([
            'notification_id' => $notification->id,
            'user_id' => $notification->user_id,
            'topic' => $topic,
            'status' => SmsDeliveryStatus::Pending,
            'due_at' => $this->window->nextSendableAt(Carbon::now()),
        ]);
    }

    /**
     * کانال پیامک فقط وقتی روشن است که مدیر روشنش کرده باشد و درایور پیامک
     * (ماژول هویت) در کار باشد.
     */
    public function enabled(): bool
    {
        return (bool) config('workspace.sms.enabled', false) && $this->container->bound(SmsSender::class);
    }

    private function muted(int $userId, SmsTopic $topic): bool
    {
        return SmsPreference::query()->where('user_id', $userId)->first()?->mutes($topic) ?? false;
    }
}
