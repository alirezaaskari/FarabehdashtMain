<?php

declare(strict_types=1);

namespace App\Modules\Expert\QuickActions;

use App\Contracts\QuickActionSource;
use App\Models\User;
use App\Support\Search\QuickAction;

/** کارهای پرسش از متخصص در پنل فرمان (Ctrl+K). */
final readonly class ExpertQuickActions implements QuickActionSource
{
    public function quickActions(?User $user): array
    {
        $actions = [new QuickAction('پرسش از متخصص', route('expert.create'), 'سؤال تازه پرسیدن')];

        if ($user !== null) {
            $actions[] = new QuickAction('پرسش‌های من', route('expert.mine'), 'سؤال‌های من پاسخ');
        }

        return $actions;
    }
}
