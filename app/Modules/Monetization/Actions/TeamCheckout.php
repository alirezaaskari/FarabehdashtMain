<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Actions;

use App\Contracts\FinancialGuard;
use App\Contracts\LedgerRecorder;
use App\Contracts\PaymentGateway;
use App\Models\User;
use App\Modules\Monetization\Domain\Enums\BillingCycle;
use App\Modules\Monetization\Domain\Enums\PeriodStatus;
use App\Modules\Monetization\Domain\Team;
use App\Modules\Monetization\Domain\TeamPeriod;
use App\Modules\Monetization\Events\TeamPaid;
use App\Modules\Monetization\Services\TeamPricing;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Ledger\LedgerEntryLine;
use App\Support\Ledger\LedgerTransactionRequest;
use App\Support\Payments\PaymentGatewayUnavailable;
use App\Support\Payments\PaymentRequest;
use App\Support\Payments\PaymentRequestResult;
use App\Support\Payments\PaymentSource;
use App\Support\Payments\WalletCheckout;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * خرید و تمدید تیم (بخش ۱۹-۶): همان مسیر اشتراک تکی — دوره در انتظار، درگاه
 * یا کیف پول، ثبت در دفتر کل به درآمد پلتفرم — با تعداد صندلی.
 *
 * تعداد صندلی تازه همان لحظه پرداخت اثر می‌کند و پایان تیم از بزرگ‌ترِ «امروز»
 * و «پایان فعلی» جلو می‌رود، تا تمدید زودهنگام روزی را نسوزاند. تعداد کمتر از
 * صندلی‌های گرفته‌شده (صاحب، اعضا و دعوت‌های باز) پذیرفته نمی‌شود.
 */
final readonly class TeamCheckout
{
    public function __construct(
        private TeamPricing $pricing,
        private PaymentGateway $gateway,
        private FinancialGuard $guard,
        private WalletCheckout $wallet,
        private LedgerRecorder $ledger,
        private DatabaseManager $db,
        private Dispatcher $events,
    ) {}

    public function open(User $owner, string $name, int $seats, BillingCycle $cycle): TeamPeriod
    {
        if ($seats < $this->pricing->minSeats() || $seats > $this->pricing->maxSeats()) {
            throw new RuntimeException(sprintf('تعداد صندلی باید بین %d و %d باشد.', $this->pricing->minSeats(), $this->pricing->maxSeats()));
        }

        return $this->db->transaction(function () use ($owner, $name, $seats, $cycle): TeamPeriod {
            $team = Team::query()->firstOrCreate(
                ['owner_id' => $owner->getKey()],
                ['uuid' => (string) Str::uuid7(), 'name' => $name],
            );

            if ($team->isCurrent() && $seats < $team->usedSeats()) {
                throw new RuntimeException('تعداد صندلی از اعضا و دعوت‌های باز کمتر است؛ اول عضوی را بردارید یا دعوتی را لغو کنید.');
            }

            $team->forceFill(['name' => $name])->save();
            $team->periods()->where('status', PeriodStatus::Pending)->update(['status' => PeriodStatus::Failed]);

            return TeamPeriod::query()->create([
                'uuid' => (string) Str::uuid7(),
                'team_id' => $team->id,
                'seats' => $seats,
                'billing_cycle' => $cycle,
                'unit_price_toman' => $this->pricing->unitPrice($seats)->toman,
                'price_toman' => $this->pricing->total($seats, $cycle)->toman,
                'status' => PeriodStatus::Pending,
            ]);
        });
    }

    public function startGateway(TeamPeriod $period, ?string $payerMobile): PaymentRequestResult
    {
        $this->guard->assertAllowed();

        try {
            $result = $this->gateway->requestPayment(new PaymentRequest(
                amount: $period->price(),
                description: 'اشتراک تیم '.$period->team->name,
                callbackUrl: route('monetization.team.callback'),
                orderUuid: $period->uuid,
                payerMobile: $payerMobile,
            ));
        } catch (PaymentGatewayUnavailable $exception) {
            $period->forceFill(['status' => PeriodStatus::Failed])->save();

            throw $exception;
        }

        $period->forceFill(['gateway_authority' => $result->authority])->save();

        return $result;
    }

    public function payFromWallet(User $owner, TeamPeriod $period): TeamPeriod
    {
        $this->guard->assertAllowed();
        $this->wallet->assertCanPay((int) $owner->getKey(), $period->price());

        return $this->complete($period, null, PaymentSource::Wallet);
    }

    public function complete(TeamPeriod $period, ?string $gatewayRefId, PaymentSource $source = PaymentSource::Gateway): TeamPeriod
    {
        if ($period->status !== PeriodStatus::Pending) {
            throw new RuntimeException('فقط دوره در انتظار پرداخت تکمیل می‌شود.');
        }

        $team = $period->team;

        $this->ledger->record(new LedgerTransactionRequest(
            kind: 'monetization.team_paid',
            idempotencyKey: 'monetization.team_paid:'.$period->uuid,
            entries: [
                new LedgerEntryLine($source->debitAccount($team->owner_id), EntryDirection::Debit, $period->price()),
                new LedgerEntryLine(new LedgerAccountRef(AccountType::PlatformRevenue), EntryDirection::Credit, $period->price()),
            ],
            referenceType: TeamPeriod::class,
            referenceId: $period->uuid,
            memo: 'پرداخت اشتراک تیم '.$period->uuid,
        ));

        $this->db->transaction(function () use ($period, $team, $gatewayRefId, $source): void {
            $now = Carbon::now();
            $startsAt = $team->isCurrent($now) ? $team->ends_at?->copy() ?? $now : $now;
            $endsAt = $startsAt->copy()->addMonths($period->billing_cycle->months());

            $period->forceFill([
                'status' => PeriodStatus::Paid,
                'gateway_ref_id' => $gatewayRefId,
                'payment_source' => $source,
                'paid_at' => $now,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ])->save();

            $team->forceFill(['seat_count' => $period->seats, 'ends_at' => $endsAt])->save();
        });

        $this->events->dispatch(new TeamPaid($team->refresh(), $period->refresh()));

        return $period;
    }
}
