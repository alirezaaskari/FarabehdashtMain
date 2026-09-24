<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Services;

use App\Contracts\WorkspaceWidgetSource;
use App\Models\User;
use App\Support\Workspace\WorkspaceView;
use App\Support\Workspace\WorkspaceWidget;

/**
 * چیدن کارت‌های میزکار از روی ماژول‌های ثبت‌شده — همان کاری که `HomePage`
 * برای صفحه اصلی می‌کند.
 */
final readonly class Dashboard
{
    /** @param  iterable<WorkspaceWidgetSource>  $sources */
    public function __construct(private iterable $sources) {}

    /** @return list<WorkspaceWidget> */
    public function widgets(User $user, WorkspaceView $view): array
    {
        $widgets = [];

        foreach ($this->sources as $source) {
            foreach ($source->widgets($user, $view) as $widget) {
                $widgets[] = $widget;
            }
        }

        usort($widgets, static fn (WorkspaceWidget $a, WorkspaceWidget $b): int => $a->order <=> $b->order);

        return $widgets;
    }
}
