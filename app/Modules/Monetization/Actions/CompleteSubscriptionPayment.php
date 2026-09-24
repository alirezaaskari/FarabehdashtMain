<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Actions;

use App\Contracts\LedgerRecorder;
use App\Modules\Monetization\Domain\Enums\PeriodStatus;
use App\Modules\Monetization\Domain\Enums\SubscriptionStatus;
use App\Modules\Monetization\Domain\Subscription;
use App\Modules\Monetization\Domain\SubscriptionPeriod;
use App\Modules\Monetization\Events\SubscriptionActivated;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Ledger\LedgerEntryLine;
use App\Support\Ledger\LedgerTransactionRequest;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * ثبت اثر مالی یک دوره اشتراک تأییدشده و جلوبردن تاریخ پایان.
 *
 * اشتراک فروش فروشنده نیست، درآمد خود پلتفرم است — پس از
 * `CommissionCalculator` عبور نمی‌کند و هیچ ردیف `vendor_payable` ندارد:
 * بدهکار خزانه، بستانکار درآمد پلتفرم. همان استدلال `CompleteOrderPayment`
 * برای خزانه («پول از بیرون سیستم وارد شد») این‌جا هم برقرار است.
 *
 * دوره تازه از بزرگ‌تر بینِ «امروز» و «پایان اشتراک فعلی» شروع می‌شود، تا
 * تمدید زودهنگام روزهای باقی‌مانده را نسوزاند.
 *
 * کلید idempotency از uuid دوره می‌آید، پس Callback تکراری زرین‌پال هرگز
 * اثر مالی دوم نمی‌گذارد.
 */
final readonly class CompleteSubscriptionPayment
{
    public function __construct(
        private LedgerRecorder $ledger,
        private DatabaseManager $db,
        private Dispatcher $events,
    ) {}

    public function handle(SubscriptionPeriod $period, string $gatewayRefId): Subscription
    {
        if ($period->status !== PeriodStatus::Pending) {
            throw new RuntimeException('فقط دوره در انتظار پرداخت تکمیل می‌شود.');
        }

        $this->ledger->record(new LedgerTransactionRequest(
            kind: 'monetization.subscription_paid',
            idempotencyKey: 'monetization.subscription_paid:'.$period->uuid,
            entries: [
                new LedgerEntryLine(new LedgerAccountRef(AccountType::Treasury), EntryDirection::Debit, $period->price()),
                new LedgerEntryLine(new LedgerAccountRef(AccountType::PlatformRevenue), EntryDirection::Credit, $period->price()),
            ],
            referenceType: SubscriptionPeriod::class,
            referenceId: $period->uuid,
            memo: 'پرداخت اشتراک '.$period->uuid,
        ));

        $subscription = $this->db->transaction(function () use ($period, $gatewayRefId): Subscription {
            $subscription = $period->subscription;

            $startsAt = $this->startFor($subscription);
            $endsAt = $startsAt->copy()->addMonths($period->billing_cycle->months());

            $period->forceFill([
                'status' => PeriodStatus::Paid,
                'gateway_ref_id' => $gatewayRefId,
                'paid_at' => Carbon::now(),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ])->save();

            $subscription->forceFill([
                'plan_id' => $period->plan_id,
                'status' => SubscriptionStatus::Active,
                'started_at' => $subscription->started_at ?? $startsAt,
                'ends_at' => $endsAt,
                'cancelled_at' => null,
            ])->save();

            return $subscription;
        });

        $this->events->dispatch(new SubscriptionActivated($subscription, $period->refresh()));

        return $subscription;
    }

    private function startFor(Subscription $subscription): Carbon
    {
        $now = Carbon::now();

        return $subscription->ends_at !== null && $subscription->ends_at->isAfter($now)
            ? $subscription->ends_at->copy()
            : $now;
    }
}
