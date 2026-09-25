<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Actions;

use App\Contracts\SmsSender;
use App\Modules\Workspace\Domain\Enums\SmsDeliveryStatus;
use App\Modules\Workspace\Domain\SmsDelivery;
use App\Modules\Workspace\Domain\UserNotification;
use App\Modules\Workspace\Services\SmsWindow;
use App\Support\Sms\SmsMessage;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * پیامک‌های رسیده به موعد را می‌فرستد.
 *
 * سقف روزانه هنگام ارسال شمرده می‌شود، نه هنگام صف‌گذاری: پیامکی که از
 * ساعت سکوت به صبح افتاده باید با پیامک‌های همان صبح در یک سقف حساب شود.
 * پیامک بیش از سقف دور ریخته می‌شود، نه به فردا؛ خبرش در صندوق اعلان هست.
 *
 * متن پیامک فقط عنوان اعلان و پیوند باز کردنش را دارد: پیوند شناسه اعلان
 * است و پس از ورود باز می‌شود، پس شماره یا داده شخصی در پیامک نمی‌رود.
 */
final readonly class SendDueNoticeSms
{
    public const TEMPLATE = 'notice';

    private const TITLE_LIMIT = 60;

    public function __construct(
        private Container $container,
        private SmsWindow $window,
        private LoggerInterface $logger,
    ) {}

    /**
     * @return int شمار پیامک‌های فرستاده‌شده
     */
    public function handle(?Carbon $at = null): int
    {
        $at ??= Carbon::now();

        if (! $this->container->bound(SmsSender::class) || $this->window->isQuiet($at)) {
            return 0;
        }

        $sender = $this->container->make(SmsSender::class);
        $sent = 0;

        SmsDelivery::query()
            ->where('status', SmsDeliveryStatus::Pending)
            ->where('due_at', '<=', $at)
            ->with(['notification', 'user'])
            // بر پایه شناسه، نه صفحه: هر ردیف همین‌جا از `Pending` بیرون می‌رود
            // و صفحه‌بندی با offset ردیف‌ها را جا می‌انداخت.
            ->lazyById(100)
            ->each(function (SmsDelivery $delivery) use ($sender, $at, &$sent): void {
                if ($this->deliver($delivery, $sender, $at)) {
                    $sent++;
                }
            });

        return $sent;
    }

    private function deliver(SmsDelivery $delivery, SmsSender $sender, Carbon $at): bool
    {
        if ($this->window->isStale($delivery->due_at, $at)) {
            $this->close($delivery, SmsDeliveryStatus::Skipped, SmsDelivery::NOTE_STALE);

            return false;
        }

        if ($this->sentToday($delivery->user_id, $at) >= $this->window->dailyLimit) {
            $this->close($delivery, SmsDeliveryStatus::Skipped, SmsDelivery::NOTE_DAILY_LIMIT);

            return false;
        }

        try {
            $sender->send($delivery->user->mobile, $this->message($delivery->notification));
        } catch (RuntimeException $exception) {
            $this->logger->warning('پیامک اعلان فرستاده نشد.', [
                'delivery_id' => $delivery->id,
                'error' => $exception->getMessage(),
            ]);

            $this->close($delivery, SmsDeliveryStatus::Failed, Str::limit($exception->getMessage(), 250));

            return false;
        }

        $delivery->update(['status' => SmsDeliveryStatus::Sent, 'sent_at' => $at]);

        return true;
    }

    private function message(UserNotification $notification): SmsMessage
    {
        $title = Str::limit($notification->title, self::TITLE_LIMIT);
        $link = route('workspace.notifications.open', $notification->uuid);

        return SmsMessage::template(self::TEMPLATE, [$title, $link], "فرابهداشت: {$title}\n{$link}");
    }

    private function sentToday(int $userId, Carbon $at): int
    {
        return SmsDelivery::query()
            ->where('user_id', $userId)
            ->where('status', SmsDeliveryStatus::Sent)
            ->where('sent_at', '>=', $at->copy()->startOfDay())
            ->count();
    }

    private function close(SmsDelivery $delivery, SmsDeliveryStatus $status, string $note): void
    {
        $delivery->update(['status' => $status, 'note' => $note]);
    }
}
