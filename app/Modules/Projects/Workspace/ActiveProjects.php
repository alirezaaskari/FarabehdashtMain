<?php

declare(strict_types=1);

namespace App\Modules\Projects\Workspace;

use App\Contracts\WorkspaceWidgetSource;
use App\Models\User;
use App\Modules\Projects\Domain\Enums\ProjectStatus;
use App\Modules\Projects\Domain\Project;
use App\Support\PersianDigits;
use App\Support\Workspace\WidgetRow;
use App\Support\Workspace\WidgetStat;
use App\Support\Workspace\WorkspaceView;
use App\Support\Workspace\WorkspaceWidget;
use Illuminate\Support\Facades\Route;

/**
 * پروژه‌های اندازه‌گیری روی نمای شخصی میزکار.
 */
final readonly class ActiveProjects implements WorkspaceWidgetSource
{
    private const LIMIT = 3;

    public function widgets(User $user, WorkspaceView $view): array
    {
        if (! $view->isPersonal() || ! Route::has('projects.index')) {
            return [];
        }

        $query = Project::query()->forUser((int) $user->getKey());

        return [new WorkspaceWidget(
            key: 'projects',
            title: 'پروژه‌های اندازه‌گیری',
            order: 15,
            stats: [new WidgetStat(
                'پروژه فعال',
                PersianDigits::from((clone $query)->where('status', ProjectStatus::Active->value)->count()),
            )],
            rows: $query->latest('updated_at')->limit(self::LIMIT)->get()
                ->map(static fn (Project $project): WidgetRow => new WidgetRow(
                    label: $project->title,
                    url: route('projects.show', $project->uuid),
                    meta: $project->status->label(),
                ))
                ->values()
                ->all(),
            empty: 'ایستگاه‌ها، دورهای اندازه‌گیری و مقایسه نتایج را در یک پروژه نگه دارید.',
            actionUrl: route('projects.index'),
            actionLabel: 'همه پروژه‌ها',
        )];
    }
}
