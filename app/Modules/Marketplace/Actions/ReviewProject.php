<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Actions;

use App\Contracts\ServiceProviderDirectory;
use App\Modules\Marketplace\Domain\Enums\ProjectStatus;
use App\Modules\Marketplace\Domain\MarketProject;
use App\Modules\Marketplace\Events\ProjectAnnounced;
use App\Modules\Marketplace\Events\ProjectReviewed;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * تصمیم مدیر درباره پروژه. تأیید همان لحظه منتشر می‌کند و مهلت پیشنهاد
 * (پیش‌فرض ۱۴ روز، DEC-79) از همین لحظه شمرده می‌شود. پروژه عمومی به
 * مشاوران و آزمایشگاه‌های هم‌خدمت یا هم‌شهر اعلان می‌شود.
 */
final readonly class ReviewProject
{
    public function __construct(
        private Repository $config,
        private Container $container,
        private Dispatcher $events,
    ) {}

    public function approve(MarketProject $project, int $adminId): MarketProject
    {
        $this->ensurePending($project);
        $now = Carbon::now();

        $project->forceFill([
            'status' => ProjectStatus::Open,
            'published_at' => $now,
            'bids_close_at' => $now->copy()->addDays((int) $this->config->get('marketplace.projects.bid_days', 14)),
            'review_note' => null,
            'reviewed_by' => $adminId,
            'reviewed_at' => $now,
        ])->save();

        $this->events->dispatch(new ProjectReviewed($project, $adminId, approved: true));

        if (! $project->is_private && $this->container->bound(ServiceProviderDirectory::class)) {
            $matching = $this->container->make(ServiceProviderDirectory::class)->matching($project->service, $project->city);
            $recipients = array_values(array_diff($matching, [$project->client_user_id]));

            if ($recipients !== []) {
                $this->events->dispatch(new ProjectAnnounced($project, $recipients));
            }
        }

        return $project;
    }

    public function reject(MarketProject $project, int $adminId, string $note): MarketProject
    {
        $note = trim($note);

        if ($note === '') {
            throw new RuntimeException('یادداشتی بنویسید که کارفرما بداند چه چیزی را اصلاح کند.');
        }

        $this->ensurePending($project);

        $project->forceFill([
            'status' => ProjectStatus::Rejected,
            'review_note' => $note,
            'reviewed_by' => $adminId,
            'reviewed_at' => Carbon::now(),
        ])->save();

        $this->events->dispatch(new ProjectReviewed($project, $adminId, approved: false));

        return $project;
    }

    private function ensurePending(MarketProject $project): void
    {
        if ($project->status !== ProjectStatus::Pending) {
            throw new RuntimeException('این پروژه در انتظار تأیید نیست.');
        }
    }
}
