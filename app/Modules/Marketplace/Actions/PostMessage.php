<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Actions;

use App\Models\User;
use App\Modules\Marketplace\Domain\Enums\BidStatus;
use App\Modules\Marketplace\Domain\Enums\MessageStatus;
use App\Modules\Marketplace\Domain\MarketBid;
use App\Modules\Marketplace\Domain\MarketMessage;
use App\Modules\Marketplace\Events\MessagePosted;
use App\Modules\Marketplace\Services\MessagePolicy;
use App\Modules\Marketplace\Services\StrikeBook;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * پیام کارفرما یا مجری در گفت‌وگوی یک پیشنهاد (DEC-80).
 *
 * در حالت «فقط مشکوک» پیام عادی همان لحظه می‌رسد و پیام مشکوک نگه داشته
 * می‌شود؛ در حالت «همه» هر پیامی تا تأیید مدیر نگه داشته می‌شود. فرستنده
 * می‌بیند پیامش در انتظار بررسی است.
 */
final readonly class PostMessage
{
    public function __construct(
        private MessagePolicy $policy,
        private StrikeBook $strikes,
        private Repository $config,
        private Dispatcher $events,
    ) {}

    public function handle(User $sender, MarketBid $bid, string $body): MarketMessage
    {
        $senderId = (int) $sender->getKey();
        $body = trim($body);
        $max = (int) $this->config->get('marketplace.messages.max', 2000);

        if (! $bid->involves($senderId)) {
            throw new RuntimeException('این گفت‌وگو مال شما نیست.');
        }

        if (in_array($bid->status, [BidStatus::Withdrawn, BidStatus::Declined], true)) {
            throw new RuntimeException('این پیشنهاد بسته شده و گفت‌وگویش دیگر پیام نمی‌پذیرد.');
        }

        if ($this->strikes->isBlocked($senderId)) {
            throw new RuntimeException('دسترسی پیام شما به‌خاطر تلاش برای ردوبدل راه تماس بسته شده است؛ مدیر باید دوباره باز کند.');
        }

        if ($body === '' || mb_strlen($body) > $max) {
            throw new RuntimeException('پیام خالی یا بلندتر از '.$max.' نویسه است.');
        }

        $flags = $this->policy->flags($body);
        $held = $this->policy->holds($body);

        $message = MarketMessage::query()->create([
            'uuid' => (string) Str::uuid7(),
            'bid_id' => $bid->id,
            'sender_user_id' => $senderId,
            'body' => $body,
            'status' => $held ? MessageStatus::Held : MessageStatus::Delivered,
            'flags' => $flags === [] ? null : $flags,
        ]);
        $message->setRelation('bid', $bid);

        $this->events->dispatch(new MessagePosted($message));

        return $message;
    }
}
