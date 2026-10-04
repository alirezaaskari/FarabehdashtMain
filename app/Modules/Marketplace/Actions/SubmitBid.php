<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Actions;

use App\Models\User;
use App\Modules\Marketplace\Domain\BidDraft;
use App\Modules\Marketplace\Domain\Enums\BidStatus;
use App\Modules\Marketplace\Domain\MarketBid;
use App\Modules\Marketplace\Domain\MarketProject;
use App\Modules\Marketplace\Events\BidSubmitted;
use App\Modules\Marketplace\Events\BidWithdrawn;
use App\Modules\Marketplace\Services\MessagePolicy;
use App\Modules\Marketplace\Services\StrikeBook;
use App\Support\Money;
use App\Support\PersianNumber;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * پیشنهاد مجری (بخش ۲۱-۳).
 *
 * فقط مشاور یا آزمایشگاه تأییدشده (توانایی `directory.listing.manage`، DEC-77)
 * پیشنهاد می‌دهد و رایگان است (DEC-78). هر مجری روی هر پروژه یک پیشنهاد دارد
 * و تا انتخاب کارفرما ویرایش یا پس‌اش می‌گیرد. سقف پیشنهاد در ۳۰ روز (DEC-86)
 * و حد مرحله‌ها (DEC-79) تنظیم پنل‌اند.
 */
final readonly class SubmitBid
{
    public const ABILITY = 'directory.listing.manage';

    public function __construct(
        private StrikeBook $strikes,
        private MessagePolicy $policy,
        private Repository $config,
        private Dispatcher $events,
    ) {}

    public function submit(User $provider, MarketProject $project, BidDraft $draft): MarketBid
    {
        $providerId = (int) $provider->getKey();
        $this->ensureCanBid($provider, $project);
        $this->ensureRules($draft);

        $bid = MarketBid::query()->where('project_id', $project->id)->where('provider_user_id', $providerId)->first();

        if ($bid !== null && $bid->status !== BidStatus::Active && $bid->status !== BidStatus::Withdrawn) {
            throw new RuntimeException('کارفرما درباره این پیشنهاد تصمیم گرفته و دیگر ویرایش نمی‌شود.');
        }

        if ($bid === null || $bid->status === BidStatus::Withdrawn) {
            $this->ensureQuota($providerId);
        }

        $attributes = [
            'cover' => $draft->cover,
            'milestones' => $draft->milestones,
            'total_toman' => $draft->totalToman(),
            'total_days' => $draft->totalDays(),
            'status' => BidStatus::Active,
        ];

        $first = $bid === null;
        $bid ??= new MarketBid(['uuid' => (string) Str::uuid7(), 'project_id' => $project->id, 'provider_user_id' => $providerId]);
        $bid->fill($attributes)->forceFill(['withdrawn_at' => null])->save();
        $bid->setRelation('project', $project);

        $this->events->dispatch(new BidSubmitted($bid, $first));

        return $bid;
    }

    public function withdraw(User $provider, MarketBid $bid): MarketBid
    {
        if ($bid->provider_user_id !== $provider->getKey() || $bid->status !== BidStatus::Active) {
            throw new RuntimeException('این پیشنهاد پس‌گرفتنی نیست.');
        }

        $bid->forceFill(['status' => BidStatus::Withdrawn, 'withdrawn_at' => Carbon::now()])->save();

        $this->events->dispatch(new BidWithdrawn($bid));

        return $bid;
    }

    public function ensureCanBid(User $provider, MarketProject $project): void
    {
        $providerId = (int) $provider->getKey();

        if (! $provider->can(self::ABILITY)) {
            throw new RuntimeException('فقط مشاور یا آزمایشگاه تأییدشده پیشنهاد می‌دهد؛ نقش را از «نقش‌ها و پروفایل‌ها» درخواست کنید.');
        }

        if ($project->client_user_id === $providerId) {
            throw new RuntimeException('روی پروژه خودتان پیشنهاد نمی‌دهید.');
        }

        if (! $project->acceptsBids()) {
            throw new RuntimeException('این پروژه دیگر پیشنهاد نمی‌پذیرد.');
        }

        if ($project->is_private && ! $project->isInvited($providerId)) {
            throw new RuntimeException('این پروژه خصوصی است و فقط دعوت‌شده‌ها پیشنهاد می‌دهند.');
        }

        if ($this->strikes->isBlocked($providerId)) {
            throw new RuntimeException('دسترسی پیشنهاد شما به‌خاطر تلاش برای ردوبدل راه تماس بسته شده است؛ مدیر باید دوباره باز کند.');
        }
    }

    private function ensureRules(BidDraft $draft): void
    {
        $max = (int) $this->config->get('marketplace.bids.milestones_max', 5);
        $min = Money::toman((int) $this->config->get('marketplace.bids.milestone_min_toman', 500_000));

        if ($draft->milestones === [] || count($draft->milestones) > $max) {
            throw new RuntimeException('پیشنهاد ۱ تا '.PersianNumber::format($max).' مرحله دارد.');
        }

        foreach ($draft->milestones as $milestone) {
            if ($milestone['amount_toman'] < $min->toman) {
                throw new RuntimeException('مبلغ هر مرحله دست‌کم '.$min->format().' است.');
            }
        }

        $texts = [$draft->cover, ...array_column($draft->milestones, 'title')];

        foreach ($texts as $text) {
            if ($this->policy->flags($text) !== []) {
                throw new RuntimeException('در متن پیشنهاد شماره، ایمیل، لینک یا نام پیام‌رسان ننویسید؛ گفت‌وگو و هماهنگی درون سایت است.');
            }
        }
    }

    private function ensureQuota(int $providerId): void
    {
        $limit = (int) $this->config->get('marketplace.bids.per_30_days', 30);
        $recent = MarketBid::query()
            ->where('provider_user_id', $providerId)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->count();

        if ($recent >= $limit) {
            throw new RuntimeException('سقف '.PersianNumber::format($limit).' پیشنهاد در ۳۰ روز پر شده است؛ چند روز دیگر دوباره سر بزنید.');
        }
    }
}
