<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Actions;

use App\Models\User;
use App\Modules\Marketplace\Domain\Enums\BidStatus;
use App\Modules\Marketplace\Domain\Enums\ContractStatus;
use App\Modules\Marketplace\Domain\Enums\MilestoneStatus;
use App\Modules\Marketplace\Domain\Enums\ProjectStatus;
use App\Modules\Marketplace\Domain\MarketBid;
use App\Modules\Marketplace\Domain\MarketContract;
use App\Modules\Marketplace\Domain\MarketProject;
use App\Modules\Marketplace\Events\ContractChanged;
use App\Modules\Marketplace\Services\ContractTerms;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * پذیرش پیشنهاد و ساخت قرارداد (بخش ۲۱-۴)، و بی‌اثر شدن قراردادی که مرحله
 * اولش در مهلت پرداخت نشد (DEC-81).
 *
 * مرحله‌ها از پیشنهاد کپی و منجمد می‌شوند و نرخ کمیسیون امروز روی قرارداد
 * ثبت می‌شود (DEC-75). پیشنهادهای دیگر بسته می‌شوند و با بی‌اثر شدن قرارداد
 * دوباره باز.
 */
final readonly class AcceptBid
{
    public function __construct(
        private ContractTerms $terms,
        private DatabaseManager $db,
        private Repository $config,
        private Dispatcher $events,
    ) {}

    public function handle(User $client, MarketBid $bid): MarketContract
    {
        if (! $this->terms->isOpen()) {
            throw new RuntimeException('پذیرش پیشنهاد در بازار پروژه فعلاً بسته است؛ قراردادهای جاری ادامه دارند.');
        }

        [$contract, $declined] = $this->db->transaction(function () use ($client, $bid): array {
            $project = MarketProject::query()->whereKey($bid->project_id)->lockForUpdate()->firstOrFail();
            $bid = MarketBid::query()->whereKey($bid->id)->lockForUpdate()->firstOrFail();

            if ($project->client_user_id !== $client->getKey()) {
                throw new RuntimeException('فقط کارفرمای همین پروژه پیشنهاد را می‌پذیرد.');
            }

            if ($project->status !== ProjectStatus::Open || $bid->status !== BidStatus::Active) {
                throw new RuntimeException('این پیشنهاد دیگر پذیرفتنی نیست.');
            }

            $contract = MarketContract::query()->create([
                'uuid' => (string) Str::uuid7(),
                'project_id' => $project->id,
                'bid_id' => $bid->id,
                'client_user_id' => $project->client_user_id,
                'provider_user_id' => $bid->provider_user_id,
                'total_toman' => $bid->total_toman,
                'commission_bp' => $this->terms->rateBp(),
                'status' => ContractStatus::AwaitingPayment,
                'pay_by' => Carbon::now()->addDays((int) $this->config->get('marketplace.contracts.pay_days', 7)),
            ]);

            foreach ($bid->milestones as $index => $milestone) {
                $contract->milestones()->create([
                    'uuid' => (string) Str::uuid7(),
                    'position' => $index + 1,
                    'title' => $milestone['title'],
                    'amount_toman' => $milestone['amount_toman'],
                    'days' => $milestone['days'],
                    'status' => MilestoneStatus::Unpaid,
                ]);
            }

            $bid->forceFill(['status' => BidStatus::Accepted])->save();
            $declined = $project->bids()->where('status', BidStatus::Active)->pluck('provider_user_id')->map(intval(...))->all();
            $project->bids()->where('status', BidStatus::Active)->update(['status' => BidStatus::Declined]);
            $project->forceFill(['status' => ProjectStatus::Awarded])->save();

            return [$contract->setRelation('project', $project), $declined];
        });

        $this->events->dispatch(new ContractChanged($contract, ContractChanged::ACCEPTED, (int) $client->getKey(), declined: $declined));

        return $contract;
    }

    /** قراردادی که تا مهلت مرحله اولش پرداخت نشد؛ پروژه دوباره برای پیشنهاد باز می‌شود. */
    public function lapse(MarketContract $contract): MarketContract
    {
        $contract = $this->db->transaction(function () use ($contract): MarketContract {
            $locked = MarketContract::query()->whereKey($contract->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== ContractStatus::AwaitingPayment || $locked->pay_by->isFuture()) {
                throw new RuntimeException('این قرارداد بی‌اثرشدنی نیست.');
            }

            $locked->forceFill(['status' => ContractStatus::Lapsed, 'lapsed_at' => Carbon::now()])->save();
            $locked->bid()->update(['status' => BidStatus::Declined]);

            $project = $locked->project;
            $project->bids()->where('status', BidStatus::Declined)->whereKeyNot($locked->bid_id)->update(['status' => BidStatus::Active]);
            $project->forceFill([
                'status' => ProjectStatus::Open,
                'bids_close_at' => Carbon::now()->addDays((int) $this->config->get('marketplace.projects.bid_days', 14)),
            ])->save();

            return $locked;
        });

        $this->events->dispatch(new ContractChanged($contract, ContractChanged::LAPSED, null));

        return $contract;
    }
}
