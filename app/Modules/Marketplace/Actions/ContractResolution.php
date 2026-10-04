<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Actions;

use App\Contracts\EscrowKeeper;
use App\Modules\Marketplace\Domain\Enums\ContractStatus;
use App\Modules\Marketplace\Domain\Enums\MilestoneStatus;
use App\Modules\Marketplace\Domain\Enums\ProjectStatus;
use App\Modules\Marketplace\Domain\MarketContract;
use App\Modules\Marketplace\Domain\MarketDispute;
use App\Modules\Marketplace\Domain\MarketMilestone;
use App\Modules\Marketplace\Events\ContractChanged;
use App\Modules\Marketplace\Services\MessagePolicy;
use App\Support\Money;
use App\Support\PersianNumber;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * اعتراض و رأی مدیر، و لغو قرارداد (بخش ۲۱-۵).
 *
 * - اعتراض روی مرحله‌ای که پولش در امانت است؛ آزادسازی خودکار تا رأی می‌ایستد.
 * - رأی: مبلغ برگشتی به کیف پول کارفرما (DEC-82)؛ کمیسیون فقط از بخش آزادشده.
 *   رأی برگشت یا تقسیم قرارداد را می‌بندد، آزادسازی کامل ادامه‌اش می‌دهد.
 * - لغو پیش از پرداخت بی‌هزینه است؛ پس از پرداخت و پیش از تحویل با موافقت
 *   مجری، و بی‌موافقت او چند روز پس از گذشتن مهلت تحویل (DEC-83).
 * - مرحله‌های آزادشده هرگز برنمی‌گردند.
 */
final readonly class ContractResolution
{
    public function __construct(
        private EscrowKeeper $escrow,
        private MilestoneFlow $flow,
        private MessagePolicy $policy,
        private Repository $config,
        private DatabaseManager $db,
        private Dispatcher $events,
    ) {}

    public function dispute(MarketMilestone $milestone, int $userId, string $reason): MarketDispute
    {
        $reason = trim($reason);
        $min = (int) $this->config->get('marketplace.contracts.reason_min', 20);

        if (mb_strlen($reason) < $min) {
            throw new RuntimeException('دلیل اعتراض را دست‌کم در '.PersianNumber::format($min).' نویسه بنویسید؛ مدیر بر همین پایه رأی می‌دهد.');
        }

        if ($this->policy->flags($reason) !== []) {
            throw new RuntimeException('در دلیل اعتراض شماره، ایمیل، لینک یا نام پیام‌رسان ننویسید.');
        }

        $dispute = null;
        $this->step($milestone, $userId, [MilestoneStatus::Funded, MilestoneStatus::Delivered, MilestoneStatus::Revising], ContractChanged::DISPUTED, function (MarketMilestone $locked) use ($userId, $reason, &$dispute): void {
            if (! $locked->contract->involves($userId)) {
                throw new RuntimeException('فقط دو طرف قرارداد اعتراض ثبت می‌کنند.');
            }

            $dispute = MarketDispute::query()->create(['uuid' => (string) Str::uuid7(), 'milestone_id' => $locked->id, 'opened_by' => $userId, 'reason' => $reason]);
            $locked->forceFill(['status' => MilestoneStatus::Disputed, 'release_at' => null, 'cancel_requested_at' => null]);
        });

        return $dispute ?? throw new RuntimeException('اعتراض ثبت نشد.');
    }

    /** رأی مدیر: صفر به کارفرما یعنی آزادسازی کامل، کل مبلغ یعنی بازگشت کامل، میانه یعنی تقسیم. */
    public function resolve(MarketMilestone $milestone, int $adminId, Money $toClient, string $note): MarketMilestone
    {
        $note = trim($note);

        if ($note === '') {
            throw new RuntimeException('رأی بدون توضیح ثبت نمی‌شود؛ هر دو طرف آن را می‌بینند.');
        }

        $milestone = $this->step($milestone, $adminId, [MilestoneStatus::Disputed], ContractChanged::RESOLVED, function (MarketMilestone $locked) use ($adminId, $toClient, $note): void {
            if ($toClient->isGreaterThan($locked->amount())) {
                throw new RuntimeException('بازگشتی از مبلغ مرحله بیشتر است.');
            }

            $uuid = (string) $locked->escrow_uuid;
            [$status, $hold] = match (true) {
                $toClient->isZero() => [MilestoneStatus::Released, $this->escrow->release($uuid, $adminId)],
                $toClient->toman === $locked->amount_toman => [MilestoneStatus::Refunded, $this->escrow->refund($uuid, $adminId, $note)],
                default => [MilestoneStatus::Settled, $this->escrow->split($uuid, $toClient, $adminId, $note)],
            };

            $locked->disputes()->whereNull('resolved_at')->update([
                'to_client_toman' => $toClient->toman,
                'note' => $note,
                'resolved_by' => $adminId,
                'resolved_at' => Carbon::now(),
            ]);
            $locked->forceFill([
                'status' => $status,
                'refunded_toman' => $hold->refunded->toman ?: null,
                'released_at' => $status === MilestoneStatus::Refunded ? null : Carbon::now(),
            ]);

            if ($status !== MilestoneStatus::Released) {
                $this->end($locked->contract);
            }
        });

        if ($milestone->status === MilestoneStatus::Released) {
            $this->flow->completeIfDone($milestone->contract, $adminId);
        }

        return $milestone;
    }

    /** لغو بی‌هزینه وقتی هیچ پولی در امانت نیست (پیش از پرداخت مرحله بعد). */
    public function cancel(MarketContract $contract, int $clientId): MarketContract
    {
        $contract = $this->db->transaction(function () use ($contract, $clientId): MarketContract {
            $locked = MarketContract::query()->whereKey($contract->id)->lockForUpdate()->firstOrFail();

            if ($locked->client_user_id !== $clientId || ! $locked->status->isOpen()) {
                throw new RuntimeException('این قرارداد لغوشدنی نیست.');
            }

            if (! $locked->holdsNothing()) {
                throw new RuntimeException('پول یک مرحله در امانت است؛ از مجری لغو بخواهید یا اعتراض ثبت کنید.');
            }

            $this->end($locked);

            return $locked;
        });

        $this->events->dispatch(new ContractChanged($contract, ContractChanged::CANCELLED, $clientId));

        return $contract;
    }

    /** پس از پرداخت و پیش از تحویل: لغو با بازگشت پول، اگر مجری موافق باشد. */
    public function requestCancel(MarketMilestone $milestone, int $clientId): MarketMilestone
    {
        return $this->step($milestone, $clientId, [MilestoneStatus::Funded], ContractChanged::CANCEL_REQUESTED, function (MarketMilestone $locked) use ($clientId): void {
            $this->assertClient($locked, $clientId);

            if ($locked->delivered_at !== null || $locked->cancel_requested_at !== null) {
                throw new RuntimeException('برای این مرحله درخواست لغو ثبت‌شدنی نیست.');
            }

            $locked->forceFill(['cancel_requested_at' => Carbon::now()]);
        });
    }

    public function respondCancel(MarketMilestone $milestone, int $providerId, bool $agree): MarketMilestone
    {
        return $this->step($milestone, $providerId, [MilestoneStatus::Funded], $agree ? ContractChanged::CANCELLED : ContractChanged::CANCEL_REFUSED, function (MarketMilestone $locked) use ($providerId, $agree): void {
            if ($locked->contract->provider_user_id !== $providerId || $locked->cancel_requested_at === null) {
                throw new RuntimeException('درخواست لغوی برای پاسخ نیست.');
            }

            $agree ? $this->refund($locked, $providerId, 'لغو با موافقت مجری') : $locked->forceFill(['cancel_requested_at' => null]);
        });
    }

    /** DEC-83: مرحله‌ای که تا چند روز پس از مهلتش هیچ تحویلی نگرفت، بی‌رأی مدیر لغو می‌شود. */
    public function cancelOverdue(MarketMilestone $milestone, int $clientId): MarketMilestone
    {
        return $this->step($milestone, $clientId, [MilestoneStatus::Funded], ContractChanged::CANCELLED, function (MarketMilestone $locked) use ($clientId): void {
            $this->assertClient($locked, $clientId);

            if (! $this->isOverdue($locked)) {
                throw new RuntimeException('مهلت لغو بی‌رأی مدیر هنوز نرسیده است.');
            }

            $this->refund($locked, $clientId, 'گذشتن مهلت تحویل');
        });
    }

    public function isOverdue(MarketMilestone $milestone): bool
    {
        return $milestone->status === MilestoneStatus::Funded
            && $milestone->delivered_at === null
            && $milestone->due_at !== null
            && $milestone->due_at->copy()->addDays((int) $this->config->get('marketplace.contracts.cancel_grace_days', 3))->isPast();
    }

    private function refund(MarketMilestone $milestone, int $actorId, string $reason): void
    {
        $hold = $this->escrow->refund((string) $milestone->escrow_uuid, $actorId, $reason);
        $milestone->forceFill(['status' => MilestoneStatus::Refunded, 'refunded_toman' => $hold->refunded->toman, 'cancel_requested_at' => null]);
        $this->end($milestone->contract);
    }

    /** قرارداد بسته می‌شود؛ مرحله‌های پرداخت‌نشده دیگر پرداخت نمی‌شوند و پروژه بسته است. */
    private function end(MarketContract $contract): void
    {
        $contract->forceFill(['status' => ContractStatus::Cancelled, 'cancelled_at' => Carbon::now()])->save();
        $contract->project->forceFill(['status' => ProjectStatus::Closed])->save();
    }

    /**
     * @param  list<MilestoneStatus>  $from
     * @param  callable(MarketMilestone): void  $change
     */
    private function step(MarketMilestone $milestone, int $actorId, array $from, string $event, callable $change): MarketMilestone
    {
        $milestone = $this->db->transaction(function () use ($milestone, $from, $change): MarketMilestone {
            $locked = MarketMilestone::query()->whereKey($milestone->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, $from, true) || ! $locked->contract->status->isOpen()) {
                throw new RuntimeException('این مرحله دیگر در این وضعیت نیست.');
            }

            $change($locked);
            $locked->save();

            return $locked;
        });

        $this->events->dispatch(new ContractChanged($milestone->contract, $event, $actorId, $milestone));

        return $milestone;
    }

    private function assertClient(MarketMilestone $milestone, int $userId): void
    {
        if ($milestone->contract->client_user_id !== $userId) {
            throw new RuntimeException('فقط کارفرمای همین قرارداد این کار را می‌کند.');
        }
    }
}
