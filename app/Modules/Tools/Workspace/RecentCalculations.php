<?php

declare(strict_types=1);

namespace App\Modules\Tools\Workspace;

use App\Contracts\WorkspaceWidgetSource;
use App\Models\User;
use App\Modules\Tools\Domain\SavedCalculation;
use App\Support\JalaliDate;
use App\Support\PersianDigits;
use App\Support\Workspace\WidgetRow;
use App\Support\Workspace\WidgetStat;
use App\Support\Workspace\WorkspaceView;
use App\Support\Workspace\WorkspaceWidget;
use Illuminate\Support\Facades\Route;

/**
 * محاسبات اخیر روی نمای شخصی میزکار.
 */
final readonly class RecentCalculations implements WorkspaceWidgetSource
{
    private const LIMIT = 3;

    public function widgets(User $user, WorkspaceView $view): array
    {
        if (! $view->isPersonal() || ! Route::has('tools.calculations.index')) {
            return [];
        }

        $query = SavedCalculation::query()->forUser((int) $user->getKey());

        return [new WorkspaceWidget(
            key: 'calculations',
            title: 'محاسبات ذخیره‌شده',
            order: 10,
            stats: [new WidgetStat('همه محاسبه‌ها', PersianDigits::from((clone $query)->count()))],
            rows: $query->latest('id')->limit(self::LIMIT)->get()
                ->map(static fn (SavedCalculation $item): WidgetRow => new WidgetRow(
                    label: $item->label ?? $item->tool_slug,
                    url: route('tools.calculations.show', $item->uuid),
                    meta: JalaliDate::short($item->created_at),
                ))
                ->values()
                ->all(),
            empty: 'نتیجه هر ابزار را با یک نام ذخیره کنید تا این‌جا بماند و قابل بازتولید باشد.',
            actionUrl: route('tools.calculations.index'),
            actionLabel: 'همه محاسبه‌ها',
        )];
    }
}
