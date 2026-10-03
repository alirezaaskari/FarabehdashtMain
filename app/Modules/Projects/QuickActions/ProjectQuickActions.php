<?php

declare(strict_types=1);

namespace App\Modules\Projects\QuickActions;

use App\Contracts\QuickActionSource;
use App\Models\User;
use App\Support\Search\QuickAction;

/** کارهای پروژه‌های اندازه‌گیری در پنل فرمان (Ctrl+K). */
final readonly class ProjectQuickActions implements QuickActionSource
{
    public function quickActions(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        return [
            new QuickAction('پروژه تازه', route('projects.index').'#title', 'ساخت جدید اندازه‌گیری'),
            new QuickAction('پروژه‌های اندازه‌گیری', route('projects.index'), 'پروژه‌های من ایستگاه تقویم'),
        ];
    }
}
