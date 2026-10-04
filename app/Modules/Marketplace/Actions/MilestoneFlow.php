<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Actions;

use App\Contracts\EscrowKeeper;
use App\Modules\Marketplace\Domain\DeliveryFile;
use App\Modules\Marketplace\Domain\Enums\ContractStatus;
use App\Modules\Marketplace\Domain\Enums\MilestoneStatus;
use App\Modules\Marketplace\Domain\Enums\ProjectStatus;
use App\Modules\Marketplace\Domain\MarketContract;
use App\Modules\Marketplace\Domain\MarketMilestone;
use App\Modules\Marketplace\Domain\MilestoneDelivery;
use App\Modules\Marketplace\Events\ContractChanged;
use App\Modules\Marketplace\Services\MessagePolicy;
use App\Support\PersianNumber;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * تحویل، تأیید، درخواست اصلاح و آزادسازی خودکار هر مرحله (DEC-81).
 *
 * آزادسازی از `EscrowKeeper` می‌گذرد: سهم مجری منهای کمیسیون به کیف پول
 * درآمدش و کمیسیون به درآمد سایت. با آزاد شدن آخرین مرحله قرارداد تمام است.
 */
final readonly class MilestoneFlow
{
    public function __construct(
        private EscrowKeeper $escrow,
        private MessagePolicy $policy,
        private Factory $storage,
        private Repository $config,
        private DatabaseManager $db,
        private Dispatcher $events,
    ) {}

    /** @param  list<UploadedFile>  $uploads */
    public function deliver(MarketMilestone $milestone, int $providerId, string $note, array $uploads = []): MarketMilestone
    {
        $note = trim($note);

        if ($this->policy->flags($note) !== []) {
            throw new RuntimeException('در توضیح تحویل شماره، ایمیل، لینک یا نام پیام‌رسان ننویسید؛ فایل را همین‌جا بگذارید.');
        }

        $max = (int) $this->config->get('marketplace.contracts.files_max', 5);

        if (count($uploads) > $max) {
            throw new RuntimeException('حداکثر '.PersianNumber::format($max).' فایل در هر تحویل.');
        }

        return $this->step($milestone, $providerId, [MilestoneStatus::Funded, MilestoneStatus::Revising], ContractChanged::DELIVERED, function (MarketMilestone $locked) use ($providerId, $note, $uploads): void {
            if ($locked->contract->provider_user_id !== $providerId) {
                throw new RuntimeException('فقط مجری همین قرارداد تحویل می‌دهد.');
            }

            $delivery = MilestoneDelivery::query()->create(['uuid' => (string) Str::uuid7(), 'milestone_id' => $locked->id, 'note' => $note]);
            $this->store($delivery, $uploads);

            $now = Carbon::now();
            $locked->forceFill([
                'status' => MilestoneStatus::Delivered,
                'delivered_at' => $now,
                'release_at' => $now->copy()->addDays((int) $this->config->get('marketplace.contracts.auto_release_days', 7)),
            ]);
        });
    }

    public function approve(MarketMilestone $milestone, int $clientId): MarketMilestone
    {
        return $this->release($milestone, $clientId, auto: false);
    }

    public function requestRevision(MarketMilestone $milestone, int $clientId, string $note): MarketMilestone
    {
        $note = trim($note);
        $max = (int) $this->config->get('marketplace.contracts.revisions_max', 2);

        return $this->step($milestone, $clientId, [MilestoneStatus::Delivered], ContractChanged::REVISION, function (MarketMilestone $locked) use ($clientId, $note, $max): void {
            $this->assertClient($locked, $clientId);

            if ($locked->revisions >= $max) {
                throw new RuntimeException('سقف '.PersianNumber::format($max).' بار اصلاح این مرحله پر شده؛ تأیید کنید یا اعتراض ثبت کنید.');
            }

            if ($this->policy->flags($note) !== []) {
                throw new RuntimeException('در توضیح اصلاح شماره، ایمیل، لینک یا نام پیام‌رسان ننویسید.');
            }

            $locked->deliveries()->latest('id')->firstOrFail()->forceFill(['revision_note' => $note, 'revision_requested_at' => Carbon::now()])->save();
            $locked->forceFill(['status' => MilestoneStatus::Revising, 'revisions' => $locked->revisions + 1, 'release_at' => null]);
        });
    }

    /** بی‌پاسخی کارفرما پس از مهلت تأیید؛ پول خودکار آزاد می‌شود. */
    public function autoRelease(MarketMilestone $milestone): MarketMilestone
    {
        if ($milestone->release_at === null || $milestone->release_at->isFuture()) {
            throw new RuntimeException('مهلت تأیید این مرحله هنوز نگذشته است.');
        }

        return $this->release($milestone, null, auto: true);
    }

    private function release(MarketMilestone $milestone, ?int $clientId, bool $auto): MarketMilestone
    {
        $milestone = $this->step($milestone, $clientId, [MilestoneStatus::Delivered], ContractChanged::RELEASED, function (MarketMilestone $locked) use ($clientId, $auto): void {
            if ($clientId !== null) {
                $this->assertClient($locked, $clientId);
            }

            $this->escrow->release((string) $locked->escrow_uuid, $clientId);
            $locked->forceFill(['status' => MilestoneStatus::Released, 'released_at' => Carbon::now(), 'release_at' => null, 'auto_released' => $auto]);
        });

        $this->completeIfDone($milestone->contract, $clientId);

        return $milestone;
    }

    private function completeIfDone(MarketContract $contract, ?int $actorId): void
    {
        $done = $this->db->transaction(function () use ($contract): bool {
            $locked = MarketContract::query()->whereKey($contract->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== ContractStatus::Active || $locked->milestones()->where('status', '!=', MilestoneStatus::Released)->exists()) {
                return false;
            }

            $locked->forceFill(['status' => ContractStatus::Completed, 'completed_at' => Carbon::now()])->save();
            $locked->project->forceFill(['status' => ProjectStatus::Completed])->save();
            $contract->setRawAttributes($locked->getAttributes(), true);

            return true;
        });

        if ($done) {
            $this->events->dispatch(new ContractChanged($contract, ContractChanged::COMPLETED, $actorId));
        }
    }

    /**
     * @param  list<MilestoneStatus>  $from
     * @param  callable(MarketMilestone): void  $change
     */
    private function step(MarketMilestone $milestone, ?int $actorId, array $from, string $event, callable $change): MarketMilestone
    {
        $milestone = $this->db->transaction(function () use ($milestone, $from, $change): MarketMilestone {
            $locked = MarketMilestone::query()->whereKey($milestone->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, $from, true)) {
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

    /** @param  list<UploadedFile>  $uploads */
    private function store(MilestoneDelivery $delivery, array $uploads): void
    {
        foreach ($uploads as $upload) {
            $uuid = (string) Str::uuid7();
            $path = $this->storage->disk('local')->putFileAs(
                (string) $this->config->get('marketplace.contracts.files_directory', 'marketplace/deliveries'),
                $upload,
                $uuid.'.'.strtolower($upload->getClientOriginalExtension() ?: 'bin'),
            );

            if ($path === false) {
                throw new RuntimeException('فایل ذخیره نشد؛ دوباره تلاش کنید.');
            }

            DeliveryFile::query()->create([
                'uuid' => $uuid,
                'delivery_id' => $delivery->id,
                'path' => $path,
                'original_name' => Str::limit($upload->getClientOriginalName(), 180, ''),
                'size_bytes' => (int) $upload->getSize(),
            ]);
        }
    }
}
