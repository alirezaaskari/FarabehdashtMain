<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Refunds;

use App\Contracts\LedgerRecorder;
use App\Contracts\RefundablePurchases;
use App\Modules\Monetization\Domain\Enums\PeriodStatus;
use App\Modules\Monetization\Domain\Team;
use App\Modules\Monetization\Domain\TeamPeriod;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Ledger\LedgerEntryLine;
use App\Support\Ledger\LedgerTransactionRequest;
use App\Support\Money;
use App\Support\Payments\RefundablePurchase;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * بازگشت وجه یک دوره اشتراک تیم به کیف پول صاحب تیم: سهم روزهای مانده، و
 * پایان حرفه‌ای همه اعضا از همین لحظه. مثل اشتراک تکی فقط آخرین دوره.
 */
final readonly class TeamRefunds implements RefundablePurchases
{
    public function __construct(
        private LedgerRecorder $ledger,
        private ConnectionInterface $db,
    ) {}

    public function label(): string
    {
        return 'اشتراک تیم';
    }

    public function paidBy(int $userId): array
    {
        $rows = [];

        foreach (Team::query()->where('owner_id', $userId)->get() as $team) {
            $latest = $this->latestPaid($team);

            $periods = TeamPeriod::query()
                ->where('team_id', $team->id)
                ->whereIn('status', [PeriodStatus::Paid->value, PeriodStatus::Refunded->value])
                ->latest('id')
                ->limit(20)
                ->get();

            foreach ($periods as $period) {
                $rows[] = new RefundablePurchase(
                    uuid: $period->uuid,
                    title: 'تیم «'.$team->name.'»، '.$period->seats.' صندلی '.$period->billing_cycle->label(),
                    paid: $period->price(),
                    refundable: $period->unusedValue(),
                    paidAt: $period->paid_at,
                    effect: 'سهم روزهای مانده به کیف پول صاحب تیم برمی‌گردد و حرفه‌ای همه اعضا از همین حالا تمام می‌شود.',
                    refunded: $period->status === PeriodStatus::Refunded,
                    blocked: $period->status === PeriodStatus::Paid && $latest?->id !== $period->id
                        ? 'دوره بعدی پشت این دوره است؛ اول آن را برگردانید.'
                        : null,
                );
            }
        }

        return $rows;
    }

    public function refund(string $uuid, int $actorId, string $reason): Money
    {
        return $this->db->transaction(function () use ($uuid, $actorId, $reason): Money {
            $period = TeamPeriod::query()->where('uuid', $uuid)->lockForUpdate()->firstOrFail();
            $team = Team::query()->lockForUpdate()->findOrFail($period->team_id);

            if ($period->status !== PeriodStatus::Paid) {
                throw new InvalidArgumentException('فقط دوره پرداخت‌شده وجه برگشتی دارد.');
            }

            if ($this->latestPaid($team)?->id !== $period->id) {
                throw new InvalidArgumentException('دوره بعدی پشت این دوره است؛ اول آن را برگردانید.');
            }

            $amount = $period->unusedValue();

            if ($amount->isZero()) {
                throw new InvalidArgumentException('از این دوره روزی نمانده که برگردد.');
            }

            $this->ledger->record(new LedgerTransactionRequest(
                kind: 'monetization.team_refunded',
                idempotencyKey: 'monetization.team_refunded:'.$period->uuid,
                entries: [
                    new LedgerEntryLine(new LedgerAccountRef(AccountType::PlatformRevenue), EntryDirection::Debit, $amount),
                    new LedgerEntryLine(LedgerAccountRef::wallet($team->owner_id), EntryDirection::Credit, $amount),
                ],
                referenceType: TeamPeriod::class,
                referenceId: $period->uuid,
                memo: 'بازگشت وجه اشتراک تیم: '.$reason,
                createdBy: $actorId,
            ));

            $period->forceFill(['status' => PeriodStatus::Refunded])->save();
            $team->forceFill(['ends_at' => Carbon::now()->max($period->starts_at ?? Carbon::now())])->save();

            return $amount;
        });
    }

    private function latestPaid(Team $team): ?TeamPeriod
    {
        return TeamPeriod::query()
            ->where('team_id', $team->id)
            ->where('status', PeriodStatus::Paid->value)
            ->orderByDesc('ends_at')
            ->first();
    }
}
