<?php

declare(strict_types=1);

namespace App\Modules\Tools\QuickActions;

use App\Contracts\QuickActionSource;
use App\Models\User;
use App\Support\Search\QuickAction;

/** کارهای ابزارها در پنل فرمان (Ctrl+K). */
final readonly class ToolQuickActions implements QuickActionSource
{
    public function quickActions(?User $user): array
    {
        $actions = [
            new QuickAction('همه ابزارها', route('tools.index'), 'محاسبه ماشین حساب ابزاریاب'),
        ];

        if ($user !== null) {
            $actions[] = new QuickAction('محاسبات ذخیره‌شده', route('tools.calculations.index'), 'محاسبه‌های من تاریخچه');
        }

        return $actions;
    }
}
