<?php

declare(strict_types=1);

namespace App\Modules\Workspace\QuickActions;

use App\Contracts\QuickActionSource;
use App\Models\User;
use App\Support\Search\QuickAction;

/** کارهای خود میزکار در پنل فرمان. */
final readonly class WorkspaceQuickActions implements QuickActionSource
{
    public function quickActions(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        return [
            new QuickAction('اعلان‌ها', route('workspace.notifications'), 'پیام خبر'),
            new QuickAction('کیف پول', route('workspace.wallet'), 'موجودی تراکنش'),
        ];
    }
}
