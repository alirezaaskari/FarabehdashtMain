<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Actions;

use App\Models\User;
use App\Modules\Marketplace\Domain\Enums\ProjectStatus;
use App\Modules\Marketplace\Domain\MarketProject;
use App\Modules\Marketplace\Events\ProjectClosed;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * کارفرما پروژه‌ای را که هنوز به قرارداد نرسیده می‌بندد. پروژه در حال انجام
 * بسته نمی‌شود؛ قرارداد قاعده لغو خودش را دارد (۲۱-۵).
 */
final readonly class CloseProject
{
    public function __construct(private Dispatcher $events) {}

    public function handle(User $user, MarketProject $project): MarketProject
    {
        if ($project->client_user_id !== $user->getKey()) {
            throw new RuntimeException('این پروژه مال شما نیست.');
        }

        if (! in_array($project->status, [ProjectStatus::Pending, ProjectStatus::Rejected, ProjectStatus::Open], true)) {
            throw new RuntimeException('این پروژه دیگر بستنی نیست.');
        }

        $project->forceFill(['status' => ProjectStatus::Closed, 'closed_at' => Carbon::now()])->save();

        $this->events->dispatch(new ProjectClosed($project, (int) $user->getKey()));

        return $project;
    }
}
