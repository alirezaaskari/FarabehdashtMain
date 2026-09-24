<?php

declare(strict_types=1);

namespace App\Modules\Reports\Workspace;

use App\Contracts\WorkspaceWidgetSource;
use App\Models\User;
use App\Modules\Reports\Domain\Report;
use App\Support\PersianDigits;
use App\Support\Workspace\WidgetRow;
use App\Support\Workspace\WidgetStat;
use App\Support\Workspace\WorkspaceView;
use App\Support\Workspace\WorkspaceWidget;
use Illuminate\Support\Facades\Route;

/**
 * گزارش‌های اخیر روی نمای شخصی میزکار.
 */
final readonly class RecentReports implements WorkspaceWidgetSource
{
    private const LIMIT = 3;

    public function widgets(User $user, WorkspaceView $view): array
    {
        if (! $view->isPersonal() || ! Route::has('reports.index')) {
            return [];
        }

        $query = Report::query()->forUser((int) $user->getKey());

        return [new WorkspaceWidget(
            key: 'reports',
            title: 'گزارش‌ها',
            order: 18,
            stats: [new WidgetStat('گزارش صادرشده', PersianDigits::from((clone $query)->issued()->count()))],
            rows: $query->latest('updated_at')->limit(self::LIMIT)->get()
                ->map(static fn (Report $report): WidgetRow => new WidgetRow(
                    label: $report->title ?? 'گزارش بی‌عنوان',
                    url: route('reports.show', $report->uuid),
                    meta: $report->status->label(),
                ))
                ->values()
                ->all(),
            empty: 'از یک پروژه یا چند محاسبه ذخیره‌شده، گزارش PDF با شناسه رهگیری بسازید.',
            actionUrl: route('reports.index'),
            actionLabel: 'همه گزارش‌ها',
        )];
    }
}
