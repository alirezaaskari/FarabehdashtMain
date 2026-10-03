<?php

declare(strict_types=1);

namespace App\Modules\Jobs\QuickActions;

use App\Contracts\QuickActionSource;
use App\Models\User;
use App\Support\Search\QuickAction;

/** کارهای کاریابی در پنل فرمان (Ctrl+K). */
final readonly class JobQuickActions implements QuickActionSource
{
    public function quickActions(?User $user): array
    {
        $actions = [new QuickAction('فرصت‌های شغلی', route('jobs.index'), 'آگهی استخدام کار')];

        if ($user?->can('jobs.post')) {
            $actions[] = new QuickAction('آگهی شغلی تازه', route('jobs.employer.postings.create'), 'ساخت جدید استخدام کارفرما');
        }

        if ($user?->can('jobs.applications.manage')) {
            $actions[] = new QuickAction('درخواست‌های شغلی من', route('jobs.applications.index'), 'رزومه ارسال');
        }

        return $actions;
    }
}
