<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Workspace;

use App\Contracts\WorkspaceWidgetSource;
use App\Models\User;
use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Monetization\Services\StreamRegistry;
use App\Modules\Monetization\Services\SubscriptionReader;
use App\Support\JalaliDate;
use App\Support\Workspace\WidgetStat;
use App\Support\Workspace\WorkspaceView;
use App\Support\Workspace\WorkspaceWidget;
use Illuminate\Support\Facades\Route;

/**
 * پلن فعلی روی نمای شخصی میزکار.
 *
 * با خاموش‌شدن کلید اشتراک، کارت هم می‌رود — همان معیار پذیرش بخش ۱۴.
 */
final readonly class PlanWidget implements WorkspaceWidgetSource
{
    public function __construct(
        private StreamRegistry $streams,
        private SubscriptionReader $subscriptions,
    ) {}

    public function widgets(User $user, WorkspaceView $view): array
    {
        if (! $view->isPersonal()
            || ! $this->streams->isEnabled(RevenueStream::ProSubscription)
            || ! Route::has('monetization.plans')) {
            return [];
        }

        $own = $this->subscriptions->ownSubscription($user);
        $hasAccess = $this->subscriptions->hasAccess($user);

        return [new WorkspaceWidget(
            key: 'plan',
            title: 'اشتراک',
            order: 40,
            stats: [
                new WidgetStat('پلن فعلی', $hasAccess ? 'حرفه‌ای' : 'رایگان'),
                ...($own?->ends_at === null ? [] : [new WidgetStat('اعتبار تا', JalaliDate::short($own->ends_at))]),
            ],
            empty: $hasAccess ? null : 'ذخیره نامحدود محاسبه و پروژه، و تخفیف فروشگاه با اشتراک حرفه‌ای.',
            actionUrl: route('monetization.plans'),
            actionLabel: $hasAccess ? 'مدیریت اشتراک' : 'دیدن پلن‌ها',
        )];
    }
}
