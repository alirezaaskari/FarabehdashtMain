<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Events;

use App\Contracts\UserNotifiableEvent;
use App\Modules\Consulting\Domain\ConsultingMessage;
use App\Support\Notifications\UserNotice;

/**
 * پیام تازه در گفت‌وگوی درخواست؛ به طرف دیگر خبر می‌دهد. متن پیام در اعلان نمی‌آید.
 */
final readonly class ConsultingMessagePosted implements UserNotifiableEvent
{
    public function __construct(public ConsultingMessage $message) {}

    public function userNotices(): array
    {
        $order = $this->message->order;
        $recipient = $this->message->user_id === $order->buyer_id ? $order->consultant_id : $order->buyer_id;

        return [new UserNotice(
            recipientId: $recipient,
            kind: 'consulting.message',
            title: 'پیام تازه درباره «'.$order->service->title.'»',
            routeName: 'consulting.orders.show',
            routeParameters: ['uuid' => $order->uuid],
        )];
    }
}
