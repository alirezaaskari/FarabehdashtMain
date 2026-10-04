<?php

declare(strict_types=1);

namespace App\Modules\Reports\QuickActions;

use App\Contracts\QuickActionSource;
use App\Models\User;
use App\Support\Search\QuickAction;

/** کارهای گزارش‌ها در پنل فرمان (Ctrl+K). */
final readonly class ReportQuickActions implements QuickActionSource
{
    public function quickActions(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        return [
            new QuickAction('گزارش تازه', route('reports.create'), 'ساخت جدید گزارش‌ساز'),
            new QuickAction('گزارش‌ها', route('reports.index'), 'گزارش‌های من'),
        ];
    }
}
