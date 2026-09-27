<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Actions;

use App\Modules\Consulting\Domain\ConsultingMessage;
use App\Modules\Consulting\Domain\ConsultingOrder;
use App\Modules\Consulting\Events\ConsultingMessagePosted;
use Illuminate\Contracts\Events\Dispatcher;
use RuntimeException;

/**
 * گفت‌وگوی خریدار و مشاور داخل صفحه درخواست (DEC-55)؛ فقط دو طرف، فقط از
 * پرداخت تا بسته‌شدن.
 */
final readonly class PostConsultingMessage
{
    public function __construct(private Dispatcher $events) {}

    public function handle(ConsultingOrder $order, int $userId, string $body): ConsultingMessage
    {
        if (! $order->isParty($userId)) {
            throw new RuntimeException('فقط خریدار و مشاور همین درخواست پیام می‌فرستند.');
        }

        if (! $order->status->isOpen()) {
            throw new RuntimeException('گفت‌وگوی این درخواست بسته است.');
        }

        $message = ConsultingMessage::query()->create([
            'order_id' => $order->id,
            'user_id' => $userId,
            'body' => trim($body),
        ]);

        $message->setRelation('order', $order);
        $this->events->dispatch(new ConsultingMessagePosted($message));

        return $message;
    }
}
