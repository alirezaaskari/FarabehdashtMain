<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Actions;

use App\Contracts\EscrowKeeper;
use App\Modules\Consulting\Domain\ConsultingOrder;
use App\Modules\Consulting\Domain\Enums\OrderStatus;
use App\Modules\Consulting\Events\ConsultingOrderChanged;
use App\Support\Escrow\EscrowHold;
use App\Support\Money;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * گام‌های پس از پرداخت: پذیرش یا رد مشاور، «انجام شد»، تأیید یا اعتراض
 * خریدار، رأی مدیر، و دو مهلت خودکار (DEC-53، DEC-54).
 *
 * هر گامی که پول را جابه‌جا می‌کند از `EscrowKeeper` می‌گذرد؛ بازگشت همیشه
 * به کیف پول است (DEC-56). ردیف درخواست پیش از هر گام قفل می‌شود تا مهلت
 * خودکار و کلیک کاربر هم‌زمان هر دو «باز» نبینند.
 */
final readonly class ConsultingOrderFlow
{
    public function __construct(
        private EscrowKeeper $escrow,
        private DatabaseManager $db,
        private Dispatcher $events,
    ) {}

    public function accept(ConsultingOrder $order, int $consultantId, string $scheduledFor, ?string $meetingLink): ConsultingOrder
    {
        return $this->step($order, $consultantId, [OrderStatus::AwaitingConsultant], ConsultingOrderChanged::ACCEPTED, function (ConsultingOrder $order) use ($consultantId, $scheduledFor, $meetingLink): void {
            $this->assertConsultant($order, $consultantId);

            $order->forceFill([
                'status' => OrderStatus::Accepted,
                'scheduled_for' => trim($scheduledFor),
                'meeting_link' => $meetingLink,
                'accepted_at' => Carbon::now(),
            ]);
        });
    }

    public function decline(ConsultingOrder $order, int $consultantId, string $reason): ConsultingOrder
    {
        $reason = $this->required($reason, 'دلیل رد را بنویسید؛ خریدار آن را می‌بیند.');

        return $this->step($order, $consultantId, [OrderStatus::AwaitingConsultant], ConsultingOrderChanged::DECLINED, function (ConsultingOrder $order) use ($consultantId, $reason): void {
            $this->assertConsultant($order, $consultantId);
            $this->closeWith($order, OrderStatus::Declined, $this->escrow->refund((string) $order->escrow_uuid, $consultantId, 'رد مشاور'), $consultantId, $reason);
        });
    }

    public function deliver(ConsultingOrder $order, int $consultantId): ConsultingOrder
    {
        return $this->step($order, $consultantId, [OrderStatus::Accepted], ConsultingOrderChanged::DELIVERED, function (ConsultingOrder $order) use ($consultantId): void {
            $this->assertConsultant($order, $consultantId);
            $order->forceFill(['status' => OrderStatus::Delivered, 'delivered_at' => Carbon::now()]);
        });
    }

    public function confirm(ConsultingOrder $order, int $buyerId): ConsultingOrder
    {
        return $this->step($order, $buyerId, [OrderStatus::Delivered], ConsultingOrderChanged::COMPLETED, function (ConsultingOrder $order) use ($buyerId): void {
            $this->assertBuyer($order, $buyerId);
            $this->closeWith($order, OrderStatus::Completed, $this->escrow->release((string) $order->escrow_uuid, $buyerId), $buyerId, null);
        });
    }

    /** خریدار پس از پذیرش (جلسه برگزار نشد) یا پس از «انجام شد» اعتراض می‌کند. */
    public function dispute(ConsultingOrder $order, int $buyerId, string $reason): ConsultingOrder
    {
        $reason = $this->required($reason, 'بنویسید چه چیزی درست انجام نشد؛ مدیر بر همین پایه رأی می‌دهد.');

        return $this->step($order, $buyerId, [OrderStatus::Accepted, OrderStatus::Delivered], ConsultingOrderChanged::DISPUTED, function (ConsultingOrder $order) use ($buyerId, $reason): void {
            $this->assertBuyer($order, $buyerId);
            $order->forceFill(['status' => OrderStatus::Disputed, 'disputed_at' => Carbon::now(), 'dispute_reason' => $reason]);
        });
    }

    /**
     * رأی مدیر در اعتراض: چه مقدار به کیف پول خریدار برگردد. صفر یعنی
     * آزادسازی کامل، کل مبلغ یعنی بازگشت کامل، میانه یعنی تقسیم.
     */
    public function resolve(ConsultingOrder $order, int $adminId, Money $toBuyer, string $note): ConsultingOrder
    {
        $note = $this->required($note, 'رأی بدون توضیح ثبت نمی‌شود؛ هر دو طرف آن را می‌بینند.');

        return $this->step($order, $adminId, [OrderStatus::Disputed], ConsultingOrderChanged::RESOLVED, function (ConsultingOrder $order) use ($adminId, $toBuyer, $note): void {
            if ($toBuyer->isGreaterThan($order->price())) {
                throw new RuntimeException('بازگشتی از مبلغ خرید بیشتر است.');
            }

            $uuid = (string) $order->escrow_uuid;
            $hold = match (true) {
                $toBuyer->isZero() => $this->escrow->release($uuid, $adminId),
                $toBuyer->toman === $order->price_toman => $this->escrow->refund($uuid, $adminId, $note),
                default => $this->escrow->split($uuid, $toBuyer, $adminId, $note),
            };

            $this->closeWith($order, OrderStatus::Resolved, $hold, $adminId, $note);
        });
    }

    /** DEC-53: درخواستی که مشاور تا مهلت پاسخ نداده، با بازگشت کامل بسته می‌شود. */
    public function expire(ConsultingOrder $order): ConsultingOrder
    {
        return $this->step($order, null, [OrderStatus::AwaitingConsultant], ConsultingOrderChanged::EXPIRED, function (ConsultingOrder $order): void {
            $this->closeWith($order, OrderStatus::Expired, $this->escrow->refund((string) $order->escrow_uuid, null, 'بی‌پاسخ ماندن مشاور'), null, null);
        });
    }

    /** DEC-54: «انجام شد» بی‌اعتراض پس از مهلت، خودکار آزاد می‌شود. */
    public function autoRelease(ConsultingOrder $order): ConsultingOrder
    {
        return $this->step($order, null, [OrderStatus::Delivered], ConsultingOrderChanged::COMPLETED, function (ConsultingOrder $order): void {
            $this->closeWith($order, OrderStatus::Completed, $this->escrow->release((string) $order->escrow_uuid), null, 'آزادسازی خودکار');
        });
    }

    /**
     * @param  list<OrderStatus>  $from
     * @param  callable(ConsultingOrder): void  $change
     */
    private function step(ConsultingOrder $order, ?int $actorId, array $from, string $event, callable $change): ConsultingOrder
    {
        $order = $this->db->transaction(function () use ($order, $from, $change): ConsultingOrder {
            $locked = ConsultingOrder::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, $from, true)) {
                throw new RuntimeException('این درخواست دیگر در این مرحله نیست.');
            }

            $change($locked);
            $locked->save();

            return $locked;
        });

        $this->events->dispatch(new ConsultingOrderChanged($order, $event, $actorId));

        return $order;
    }

    private function closeWith(ConsultingOrder $order, OrderStatus $status, EscrowHold $hold, ?int $actorId, ?string $note): void
    {
        $order->forceFill([
            'status' => $status,
            'refunded_toman' => $hold->refunded->toman,
            'closed_at' => Carbon::now(),
            'closed_by' => $actorId,
            'close_note' => $note,
        ]);
    }

    private function assertConsultant(ConsultingOrder $order, int $userId): void
    {
        if ($order->consultant_id !== $userId) {
            throw new RuntimeException('فقط مشاور همین درخواست این کار را می‌کند.');
        }
    }

    private function assertBuyer(ConsultingOrder $order, int $userId): void
    {
        if ($order->buyer_id !== $userId) {
            throw new RuntimeException('فقط خریدار همین درخواست این کار را می‌کند.');
        }
    }

    private function required(string $text, string $message): string
    {
        $text = trim($text);

        return $text !== '' ? $text : throw new RuntimeException($message);
    }
}
