<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Services;

use App\Contracts\WorkspaceWidgetSource;
use App\Models\User;
use App\Support\Workspace\WidgetRow;
use App\Support\Workspace\WidgetStat;
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

    /**
     * نوار آمار بالای میزکار: نخستین آمار هر کارت، به ترتیب کارت‌ها.
     *
     * آمار صفر کنار گذاشته می‌شود: ستونی از «۰»ها چیزی به کاربر نمی‌گوید و
     * کاربر تازه به‌جایش قدم‌های شروع را می‌بیند.
     *
     * @param  list<WorkspaceWidget>  $widgets
     * @return list<WidgetStat>
     */
    public function highlights(array $widgets, int $limit = 4): array
    {
        $stats = [];

        foreach ($widgets as $widget) {
            if ($widget->stats !== [] && self::isNonZero($widget->stats[0]->value)) {
                $stats[] = new WidgetStat($widget->title, $widget->stats[0]->value);
            }
        }

        return array_slice($stats, 0, $limit);
    }

    /**
     * «آخرین فعالیت‌ها»: ردیف‌های همه کارت‌ها، یکی‌یکی از هر کارت.
     *
     * ردیف‌ها زمان مشترک ندارند؛ چینش نوبتی نمی‌گذارد یک ماژول پرکار بقیه را
     * از فهرست بیرون کند.
     *
     * @param  list<WorkspaceWidget>  $widgets
     * @return list<WidgetRow>
     */
    public function activity(array $widgets, int $limit = 6): array
    {
        $queues = array_values(array_filter(array_map(
            static fn (WorkspaceWidget $widget): array => $widget->rows,
            $widgets,
        )));
        $rows = [];

        for ($i = 0; count($rows) < $limit && $queues !== []; $i++) {
            foreach ($queues as $key => $queue) {
                if (! isset($queue[$i])) {
                    unset($queues[$key]);

                    continue;
                }

                $rows[] = $queue[$i];
            }
        }

        return array_slice($rows, 0, $limit);
    }

    /** مقدار از قبل قالب‌بندی شده (ارقام فارسی یا لاتین، شاید با واحد). */
    private static function isNonZero(string $value): bool
    {
        return preg_match('/[1-9۱-۹]/u', $value) === 1;
    }
}
