<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Actions;

use App\Contracts\EscrowKeeper;
use App\Modules\Consulting\Domain\ConsultingOrder;
use App\Modules\Consulting\Domain\Enums\OrderStatus;
use App\Modules\Consulting\Events\ConsultingOrderChanged;
use App\Support\Escrow\EscrowHold;
use App\Support\Money;
use Illuminate\Contracts\Config\Repository;
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
        private Repository $config,
    ) {}

    /**
     * پذیرش. جلسه و بازدید زمان قطعی می‌خواهند؛ بررسی گزارش به‌جایش مهلت
     * تحویل می‌گیرد (DEC-57).
     */
    public function accept(ConsultingOrder $order, int $consultantId, ?string $scheduledFor = null, ?string $meetingLink = null): ConsultingOrder
    {
        $review = $order->isReportReview();

        if (! $review) {
            $scheduledFor = $this->required((string) $scheduledFor, 'زمان قطعی جلسه را بنویسید.');
        }

        return $this->step($order, $consultantId, [OrderStatus::AwaitingConsultant], ConsultingOrderChanged::ACCEPTED, function (ConsultingOrder $order) use ($consultantId, $scheduledFor, $meetingLink, $review): void {
            $this->assertConsultant($order, $consultantId);

            $order->forceFill([
                'status' => OrderStatus::Accepted,
                'scheduled_for' => $review ? null : $scheduledFor,
                'meeting_link' => $review ? null : $meetingLink,
                'accepted_at' => Carbon::now(),
                'due_at' => $review ? Carbon::now()->addDays((int) $this->config->get('consulting.reviews.due_days', 5)) : null,
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
        if ($order->isReportReview()) {
            throw new RuntimeException('بررسی گزارش با فرستادن یادداشت‌ها و جمع‌بندی تحویل می‌شود.');
        }

        return $this->step($order, $consultantId, [OrderStatus::Accepted], ConsultingOrderChanged::DELIVERED, function (ConsultingOrder $order) use ($consultantId): void {
            $this->assertConsultant($order, $consultantId);
            $order->forceFill(['status' => OrderStatus::Delivered, 'delivered_at' => Carbon::now()]);
        });
    }

    /**
     * تحویل بررسی گزارش: یادداشت هر بخش (اختیاری) و جمع‌بندی (لازم). پس از
     * تحویل ویرایش نمی‌شود؛ پرسش تکمیلی پاسخ جدا دارد.
     *
     * @param  array<string, string|null>  $notes  کلید بخش => یادداشت
     */
    public function submitReview(ConsultingOrder $order, int $consultantId, array $notes, string $summary): ConsultingOrder
    {
        $summary = $this->required($summary, 'جمع‌بندی بررسی را بنویسید.');
        $notes = array_filter(array_map(static fn (?string $note): string => trim((string) $note), $notes), static fn (string $note): bool => $note !== '');

        return $this->step($order, $consultantId, [OrderStatus::Accepted], ConsultingOrderChanged::DELIVERED, function (ConsultingOrder $order) use ($consultantId, $notes, $summary): void {
            $this->assertConsultant($order, $consultantId);
            $this->assertReview($order);

            $order->forceFill([
                'status' => OrderStatus::Delivered,
                'review' => ['notes' => $notes, 'summary' => $summary],
                'delivered_at' => Carbon::now(),
            ]);
        });
    }

    /** DEC-57: خریدار پس از تحویل یک بار پرسش تکمیلی می‌فرستد؛ مهلت آزادسازی تا پاسخ می‌ایستد. */
    public function askFollowUp(ConsultingOrder $order, int $buyerId, string $question): ConsultingOrder
    {
        $question = $this->required($question, 'پرسش تکمیلی را بنویسید.');

        return $this->step($order, $buyerId, [OrderStatus::Delivered], ConsultingOrderChanged::FOLLOW_UP, function (ConsultingOrder $order) use ($buyerId, $question): void {
            $this->assertBuyer($order, $buyerId);
            $this->assertReview($order);

            if ($order->follow_up_question !== null) {
                throw new RuntimeException('پرسش تکمیلی فقط یک بار ممکن است.');
            }

            $order->forceFill(['status' => OrderStatus::FollowUp, 'follow_up_question' => $question, 'follow_up_asked_at' => Carbon::now()]);
        });
    }

    /** پاسخ پرسش تکمیلی؛ درخواست دوباره «انجام شد» می‌شود و مهلت آزادسازی از نو شروع. */
    public function answerFollowUp(ConsultingOrder $order, int $consultantId, string $answer): ConsultingOrder
    {
        $answer = $this->required($answer, 'پاسخ پرسش تکمیلی را بنویسید.');

        return $this->step($order, $consultantId, [OrderStatus::FollowUp], ConsultingOrderChanged::ANSWERED, function (ConsultingOrder $order) use ($consultantId, $answer): void {
            $this->assertConsultant($order, $consultantId);
            $order->forceFill(['status' => OrderStatus::Delivered, 'follow_up_answer' => $answer, 'delivered_at' => Carbon::now()]);
        });
    }

    /** بررسی گزارشی که تا مهلت تحویل نرسیده، به انتخاب خریدار با بازگشت کامل بسته می‌شود. */
    public function cancelOverdue(ConsultingOrder $order, int $buyerId): ConsultingOrder
    {
        return $this->step($order, $buyerId, [OrderStatus::Accepted], ConsultingOrderChanged::CANCELLED, function (ConsultingOrder $order) use ($buyerId): void {
            $this->assertBuyer($order, $buyerId);

            if (! $order->isOverdue()) {
                throw new RuntimeException('مهلت تحویل هنوز نگذشته است.');
            }

            $this->closeWith($order, OrderStatus::Cancelled, $this->escrow->refund((string) $order->escrow_uuid, $buyerId, 'گذشتن مهلت تحویل'), $buyerId, null);
        });
    }

    public function confirm(ConsultingOrder $order, int $buyerId): ConsultingOrder
    {
        return $this->step($order, $buyerId, [OrderStatus::Delivered], ConsultingOrderChanged::COMPLETED, function (ConsultingOrder $order) use ($buyerId): void {
            $this->assertBuyer($order, $buyerId);
            $this->closeWith($order, OrderStatus::Completed, $this->escrow->release((string) $order->escrow_uuid, $buyerId), $buyerId, null);
        });
    }

    /** خریدار پس از پذیرش (جلسه برگزار نشد)، پس از «انجام شد» یا در انتظار پاسخ پرسش تکمیلی اعتراض می‌کند. */
    public function dispute(ConsultingOrder $order, int $buyerId, string $reason): ConsultingOrder
    {
        $reason = $this->required($reason, 'بنویسید چه چیزی درست انجام نشد؛ مدیر بر همین پایه رأی می‌دهد.');

        return $this->step($order, $buyerId, [OrderStatus::Accepted, OrderStatus::Delivered, OrderStatus::FollowUp], ConsultingOrderChanged::DISPUTED, function (ConsultingOrder $order) use ($buyerId, $reason): void {
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

    private function assertReview(ConsultingOrder $order): void
    {
        if (! $order->isReportReview()) {
            throw new RuntimeException('این درخواست بررسی گزارش نیست.');
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
