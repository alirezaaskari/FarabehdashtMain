<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Consulting\Domain\ConsultingOrder;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;
use App\Support\PersianNumber;

/**
 * هر گام درخواست خدمت: پرداخت، پذیرش، رد، انجام، تأیید، اعتراض، رأی مدیر.
 *
 * دفتر رویداد فقط شناسه و مبلغ می‌گیرد؛ شرح نیاز و پیام‌ها در آن نمی‌آیند.
 * هر گام به طرفی خبر می‌دهد که حالا نوبت اوست.
 */
final readonly class ConsultingOrderChanged implements AuditableEvent, UserNotifiableEvent
{
    public const PAID = 'paid';

    public const ACCEPTED = 'accepted';

    public const DECLINED = 'declined';

    public const EXPIRED = 'expired';

    public const DELIVERED = 'delivered';

    public const COMPLETED = 'completed';

    public const DISPUTED = 'disputed';

    public const RESOLVED = 'resolved';

    public const FOLLOW_UP = 'follow_up';

    public const ANSWERED = 'answered';

    public const CANCELLED = 'cancelled';

    public function __construct(
        public ConsultingOrder $order,
        public string $step,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'consulting.order_'.$this->step,
            subjectType: ConsultingOrder::class,
            subjectId: $this->order->uuid,
            actorId: $this->actorId,
            after: ['status' => $this->order->status->value],
            context: array_filter([
                'price_toman' => $this->order->price_toman,
                'refunded_toman' => $this->order->refunded_toman ?: null,
                'escrow' => $this->order->escrow_uuid,
            ], static fn (mixed $value): bool => $value !== null),
        );
    }

    public function userNotices(): array
    {
        $title = $this->order->service->title;
        $buyer = $this->order->buyer_id;
        $consultant = $this->order->consultant_id;

        $notices = match ($this->step) {
            self::PAID => [[$consultant, 'درخواست خدمت تازه', 'برای «'.$title.'» درخواست پرداخت‌شده دارید؛ تا '.PersianNumber::format(self::replyHours()).' ساعت بپذیرید یا رد کنید.']],
            self::ACCEPTED => [[$buyer, 'مشاور درخواست شما را پذیرفت', $this->order->isReportReview()
                ? 'بررسی گزارش شروع شد؛ مهلت تحویل در صفحه درخواست است.'
                : 'زمان و جزئیات جلسه «'.$title.'» در صفحه درخواست است.']],
            self::DECLINED => [[$buyer, 'مشاور درخواست را نپذیرفت', 'کل مبلغ «'.$title.'» به کیف پول شما برگشت.']],
            self::EXPIRED => [
                [$buyer, 'درخواست بی‌پاسخ ماند', 'مشاور در '.PersianNumber::format(self::replyHours()).' ساعت پاسخ نداد و کل مبلغ «'.$title.'» به کیف پول شما برگشت.'],
                [$consultant, 'درخواست بی‌پاسخ بسته شد', 'درخواست «'.$title.'» در '.PersianNumber::format(self::replyHours()).' ساعت پاسخ نگرفت و پول به خریدار برگشت.'],
            ],
            self::DELIVERED => [[$buyer, 'مشاور کار را انجام‌شده اعلام کرد', 'اگر «'.$title.'» انجام شد تأیید کنید، وگرنه اعتراض ثبت کنید؛ بی‌پاسخ، '.PersianNumber::format((int) config('consulting.orders.auto_release_days', 7)).' روز بعد پول آزاد می‌شود.']],
            self::COMPLETED => [[$consultant, 'مبلغ خدمت آزاد شد', 'سهم شما از «'.$title.'» به کیف پول درآمد رفت و از مسیر تسویه برداشت‌پذیر است.']],
            self::DISPUTED => [[$consultant, 'خریدار اعتراض ثبت کرد', 'درخواست «'.$title.'» در بررسی مدیر است؛ پول تا رأی مدیر در امانت می‌ماند.']],
            self::FOLLOW_UP => [[$consultant, 'پرسش تکمیلی درباره بررسی', 'خریدار «'.$title.'» یک پرسش تکمیلی فرستاده است.']],
            self::ANSWERED => [[$buyer, 'پاسخ پرسش تکمیلی رسید', 'پاسخ مشاور در صفحه درخواست است؛ تأیید کنید یا اعتراض ثبت کنید.']],
            self::CANCELLED => [[$consultant, 'درخواست پس از مهلت لغو شد', 'بررسی «'.$title.'» تا مهلت تحویل نرسید و خریدار لغو کرد؛ پول به او برگشت.']],
            self::RESOLVED => [
                [$buyer, 'اعتراض بررسی شد', 'رأی مدیر درباره «'.$title.'» در صفحه درخواست است.'],
                [$consultant, 'اعتراض بررسی شد', 'رأی مدیر درباره «'.$title.'» در صفحه درخواست است.'],
            ],
            default => [],
        };

        return array_map(fn (array $notice): UserNotice => new UserNotice(
            recipientId: $notice[0],
            kind: 'consulting.order_'.$this->step,
            title: $notice[1],
            body: $notice[2],
            routeName: 'consulting.orders.show',
            routeParameters: ['uuid' => $this->order->uuid],
        ), $notices);
    }

    /** مهلت پاسخ مشاور، همان عددی که مدیر در پنل «قیمت‌ها و زمان‌ها» گذاشته. */
    private static function replyHours(): int
    {
        return (int) config('consulting.orders.reply_hours', 48);
    }
}
