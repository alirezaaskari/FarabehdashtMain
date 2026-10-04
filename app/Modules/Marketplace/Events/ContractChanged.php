<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Events;

use App\Contracts\AuditableEvent;
use App\Contracts\UserNotifiableEvent;
use App\Modules\Marketplace\Domain\MarketContract;
use App\Modules\Marketplace\Domain\MarketMilestone;
use App\Support\Audit\AuditEntry;
use App\Support\Notifications\UserNotice;
use App\Support\PersianNumber;

/**
 * هر گام قرارداد بازار پروژه: پذیرش، پرداخت مرحله، تحویل، اصلاح،
 * آزادسازی، پایان و بی‌اثر شدن.
 *
 * دفتر رویداد فقط شناسه و مبلغ می‌گیرد، نه متن تحویل. هر گام به طرفی خبر
 * می‌دهد که حالا نوبت اوست.
 */
final readonly class ContractChanged implements AuditableEvent, UserNotifiableEvent
{
    public const ACCEPTED = 'accepted';

    public const FUNDED = 'funded';

    public const DELIVERED = 'delivered';

    public const REVISION = 'revision';

    public const RELEASED = 'released';

    public const COMPLETED = 'completed';

    public const LAPSED = 'lapsed';

    public const DISPUTED = 'disputed';

    public const RESOLVED = 'resolved';

    public const CANCEL_REQUESTED = 'cancel_requested';

    public const CANCEL_REFUSED = 'cancel_refused';

    public const CANCELLED = 'cancelled';

    /** @param  list<int>  $declined  مجری‌هایی که پیشنهادشان با این پذیرش بسته شد */
    public function __construct(
        public MarketContract $contract,
        public string $step,
        public ?int $actorId,
        public ?MarketMilestone $milestone = null,
        public array $declined = [],
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'marketplace.contract_'.$this->step,
            subjectType: MarketContract::class,
            subjectId: $this->contract->uuid,
            actorId: $this->actorId,
            after: ['status' => $this->contract->status->value] + ($this->milestone ? ['milestone_status' => $this->milestone->status->value] : []),
            context: array_filter([
                'project_id' => $this->contract->project_id,
                'milestone' => $this->milestone?->uuid,
                'amount_toman' => $this->milestone->amount_toman ?? $this->contract->total_toman,
                'escrow' => $this->milestone?->escrow_uuid,
                'auto' => $this->milestone?->auto_released ?: null,
                'refunded_toman' => $this->milestone?->refunded_toman ?: null,
            ], static fn (mixed $value): bool => $value !== null),
        );
    }

    public function userNotices(): array
    {
        $title = $this->contract->project->title;
        $client = $this->contract->client_user_id;
        $provider = $this->contract->provider_user_id;
        $stage = $this->milestone ? 'مرحله '.PersianNumber::format($this->milestone->position).' «'.$this->milestone->title.'»' : '';

        $notices = match ($this->step) {
            self::ACCEPTED => [
                [$provider, 'marketplace.contract_accepted', 'پیشنهاد شما پذیرفته شد', 'قرارداد «'.$title.'» ساخته شد. با پرداخت مرحله اول خبر می‌گیرید که کار را شروع کنید.'],
                ...array_map(static fn (int $id): array => [$id, 'marketplace.bid_declined', 'کارفرما مجری دیگری انتخاب کرد', 'پروژه «'.$title.'» به مجری دیگری سپرده شد.'], $this->declined),
            ],
            self::FUNDED => [[$provider, 'marketplace.milestone_funded', 'پول مرحله در امانت است', 'پول '.$stage.' از «'.$title.'» در امانت فرابهداشت است؛ کار را شروع کنید.']],
            self::DELIVERED => [[$client, 'marketplace.milestone_delivered', 'مرحله تحویل شد', $stage.' از «'.$title.'» تحویل شد. تأیید کنید یا اصلاح بخواهید؛ بی‌پاسخ، '.PersianNumber::format(self::releaseDays()).' روز بعد پول خودکار آزاد می‌شود.']],
            self::REVISION => [[$provider, 'marketplace.milestone_revision', 'کارفرما اصلاح خواست', 'توضیح کارفرما درباره '.$stage.' از «'.$title.'» در صفحه قرارداد است.']],
            self::RELEASED => array_filter([
                [$provider, 'marketplace.milestone_released', 'پول مرحله آزاد شد', 'سهم شما از '.$stage.' «'.$title.'» به کیف پول درآمد رفت و از مسیر تسویه برداشت‌پذیر است.'],
                $this->milestone?->auto_released ? [$client, 'marketplace.milestone_auto_released', 'پول مرحله خودکار آزاد شد', $stage.' از «'.$title.'» در مهلت تأیید پاسخی نگرفت و پولش به مجری رسید.'] : null,
            ]),
            self::COMPLETED => [
                [$client, 'marketplace.contract_completed', 'پروژه تمام شد', 'همه مرحله‌های «'.$title.'» تحویل و آزاد شد.'],
                [$provider, 'marketplace.contract_completed', 'پروژه تمام شد', 'همه مرحله‌های «'.$title.'» تحویل و آزاد شد.'],
            ],
            self::LAPSED => [
                [$client, 'marketplace.contract_lapsed', 'قرارداد بی‌اثر شد', 'مرحله اول «'.$title.'» در مهلت پرداخت نشد؛ پروژه دوباره برای پیشنهاد باز است.'],
                [$provider, 'marketplace.contract_lapsed', 'قرارداد بی‌اثر شد', 'کارفرما مرحله اول «'.$title.'» را در مهلت نپرداخت و قرارداد بی‌اثر شد.'],
            ],
            self::DISPUTED => [
                [$this->counterpart(), 'marketplace.milestone_disputed', 'اعتراض ثبت شد', 'برای '.$stage.' از «'.$title.'» اعتراض ثبت شد؛ پول تا رأی مدیر در امانت می‌ماند.'],
            ],
            self::RESOLVED => [
                [$client, 'marketplace.dispute_resolved', 'رأی مدیر ثبت شد', 'رأی مدیر درباره '.$stage.' از «'.$title.'» در صفحه قرارداد است.'],
                [$provider, 'marketplace.dispute_resolved', 'رأی مدیر ثبت شد', 'رأی مدیر درباره '.$stage.' از «'.$title.'» در صفحه قرارداد است.'],
            ],
            self::CANCEL_REQUESTED => [[$provider, 'marketplace.cancel_requested', 'کارفرما لغو خواست', 'کارفرما برای '.$stage.' از «'.$title.'» لغو با بازگشت پول خواسته؛ موافقت کنید یا نپذیرید.']],
            self::CANCEL_REFUSED => [[$client, 'marketplace.cancel_refused', 'مجری لغو را نپذیرفت', 'برای '.$stage.' از «'.$title.'» می‌توانید اعتراض ثبت کنید تا مدیر رأی بدهد.']],
            self::CANCELLED => [
                [$client, 'marketplace.contract_cancelled', 'قرارداد لغو شد', 'قرارداد «'.$title.'» لغو شد'.($this->milestone?->refunded_toman ? ' و پول مرحله در امانت به کیف پول شما برگشت.' : '.')],
                [$provider, 'marketplace.contract_cancelled', 'قرارداد لغو شد', 'قرارداد «'.$title.'» لغو شد.'],
            ],
            default => [],
        };

        return array_map(fn (array $notice): UserNotice => new UserNotice(
            recipientId: $notice[0],
            kind: $notice[1],
            title: $notice[2],
            body: $notice[3],
            routeName: $notice[1] === 'marketplace.bid_declined' ? 'market.bids.mine' : 'market.contracts.show',
            routeParameters: $notice[1] === 'marketplace.bid_declined' ? [] : ['uuid' => $this->contract->uuid],
        ), $notices);
    }

    /** طرف مقابل کسی که این گام را برداشت. */
    private function counterpart(): int
    {
        return $this->actorId === $this->contract->client_user_id ? $this->contract->provider_user_id : $this->contract->client_user_id;
    }

    private static function releaseDays(): int
    {
        return (int) config('marketplace.contracts.auto_release_days', 7);
    }
}
