<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Refunds;

use App\Contracts\LedgerRecorder;
use App\Contracts\RefundablePurchases;
use App\Modules\Monetization\Domain\Enums\PeriodStatus;
use App\Modules\Monetization\Domain\Subscription;
use App\Modules\Monetization\Domain\SubscriptionPeriod;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Ledger\LedgerEntryLine;
use App\Support\Ledger\LedgerTransactionRequest;
use App\Support\Money;
use App\Support\Payments\PaymentSource;
use App\Support\Payments\RefundablePurchase;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * بازگشت وجه یک دوره پرداخت اشتراک حرفه‌ای: سهم روزهای مانده به کیف پول و
 * پایان اشتراک از همین لحظه. فقط آخرین دوره برمی‌گردد، چون دوره‌های بعدی
 * پشت سر همین دوره چیده شده‌اند و برگرداندن وسطی روزهایش را رایگان می‌کرد.
 */
final readonly class SubscriptionRefunds implements RefundablePurchases
{
    public function __construct(
        private LedgerRecorder $ledger,
        private ConnectionInterface $db,
    ) {}

    public function label(): string
    {
        return 'اشتراک حرفه‌ای';
    }

    public function paidBy(int $userId): array
    {
        $subscription = Subscription::query()->where('user_id', $userId)->first();

        if ($subscription === null) {
            return [];
        }

        $periods = SubscriptionPeriod::query()
            ->where('subscription_id', $subscription->id)
            ->whereIn('status', [PeriodStatus::Paid->value, PeriodStatus::Refunded->value])
            ->latest('id')
            ->limit(20)
            ->get();

        $latest = $this->latestPaid($subscription);

        return $periods
            ->filter(fn (SubscriptionPeriod $period): bool => $period->price_toman > 0 || $period->status === PeriodStatus::Refunded)
            ->map(fn (SubscriptionPeriod $period): RefundablePurchase => new RefundablePurchase(
                uuid: $period->uuid,
                title: 'حرفه‌ای '.$period->billing_cycle->label(),
                paid: $period->price(),
                refundable: $period->unusedValue(),
                paidAt: $period->paid_at,
                effect: 'سهم روزهای مانده برمی‌گردد و اشتراک از همین حالا تمام می‌شود.',
                refunded: $period->status === PeriodStatus::Refunded,
                blocked: $this->blocked($period, $latest),
            ))
            ->values()
            ->all();
    }

    public function refund(string $uuid, int $actorId, string $reason): Money
    {
        return $this->db->transaction(function () use ($uuid, $actorId, $reason): Money {
            $period = SubscriptionPeriod::query()->where('uuid', $uuid)->lockForUpdate()->firstOrFail();
            $subscription = Subscription::query()->lockForUpdate()->findOrFail($period->subscription_id);

            $blocked = $period->status === PeriodStatus::Paid ? $this->blocked($period, $this->latestPaid($subscription)) : 'فقط دوره پرداخت‌شده وجه برگشتی دارد.';
            $amount = $period->unusedValue();

            if ($blocked !== null) {
                throw new InvalidArgumentException($blocked);
            }

            if ($amount->isZero()) {
                throw new InvalidArgumentException('از این دوره روزی نمانده که برگردد.');
            }

            $this->ledger->record(new LedgerTransactionRequest(
                kind: 'monetization.subscription_refunded',
                idempotencyKey: 'monetization.subscription_refunded:'.$period->uuid,
                entries: [
                    new LedgerEntryLine(new LedgerAccountRef(AccountType::PlatformRevenue), EntryDirection::Debit, $amount),
                    new LedgerEntryLine(LedgerAccountRef::wallet($subscription->user_id), EntryDirection::Credit, $amount),
                ],
                referenceType: SubscriptionPeriod::class,
                referenceId: $period->uuid,
                memo: 'بازگشت وجه اشتراک حرفه‌ای: '.$reason,
                createdBy: $actorId,
            ));

            $endsAt = Carbon::now()->max($period->starts_at ?? Carbon::now());
            $period->forceFill(['status' => PeriodStatus::Refunded])->save();
            $subscription->forceFill(['ends_at' => $endsAt])->save();

            return $amount;
        });
    }

    private function latestPaid(Subscription $subscription): ?SubscriptionPeriod
    {
        return SubscriptionPeriod::query()
            ->where('subscription_id', $subscription->id)
            ->where('status', PeriodStatus::Paid->value)
            ->orderByDesc('ends_at')
            ->first();
    }

    private function blocked(SubscriptionPeriod $period, ?SubscriptionPeriod $latest): ?string
    {
        if ($period->status !== PeriodStatus::Paid) {
            return null;
        }

        if (! in_array($period->payment_source, [PaymentSource::Gateway, PaymentSource::Wallet], true)) {
            return 'این دوره از بسته راه‌حل آمده؛ از خود بسته برگردانید.';
        }

        if ($latest !== null && $latest->id !== $period->id) {
            return 'دوره بعدی پشت این دوره است؛ اول آن را برگردانید.';
        }

        return null;
    }
}
