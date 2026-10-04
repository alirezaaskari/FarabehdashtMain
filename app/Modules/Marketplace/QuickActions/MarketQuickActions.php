<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\QuickActions;

use App\Contracts\QuickActionSource;
use App\Models\User;
use App\Modules\Marketplace\Actions\SubmitBid;
use App\Support\Search\QuickAction;

/** کارهای بازار پروژه در پنل فرمان (Ctrl+K). */
final readonly class MarketQuickActions implements QuickActionSource
{
    public function quickActions(?User $user): array
    {
        $actions = [new QuickAction('بازار پروژه', route('market.index'), 'پروژه مشاور آزمایشگاه پیمانکار')];

        if ($user === null) {
            return $actions;
        }

        $actions[] = new QuickAction('تعریف پروژه در بازار', route('market.client.create'), 'پروژه تازه کارفرما برون‌سپاری');
        $actions[] = new QuickAction('پروژه‌های بازار من', route('market.client.index'), 'پیشنهادها کارفرما');

        if ($user->can(SubmitBid::ABILITY)) {
            $actions[] = new QuickAction('پیشنهادهای من در بازار', route('market.bids.mine'), 'مشاور آزمایشگاه دعوت');
        }

        return $actions;
    }
}
