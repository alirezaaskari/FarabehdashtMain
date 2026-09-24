<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Http\Controllers;

use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Monetization\Services\PlanCatalog;
use App\Modules\Monetization\Services\StreamRegistry;
use App\Modules\Monetization\Services\SubscriptionReader;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * صفحه پلن‌های اشتراک.
 *
 * وقتی کلید اشتراک خاموش است این صفحه ۴۰۴ نمی‌شود، به خانه هدایت می‌شود —
 * قاعده سند کلیدها. نشانی‌ای که کاربر بوکمارک کرده یا گوگل ایندکس کرده
 * نباید یک‌شبه خطا بدهد.
 */
final readonly class PlansController
{
    public function __construct(
        private PlanCatalog $plans,
        private StreamRegistry $streams,
        private SubscriptionReader $subscriptions,
    ) {}

    public function __invoke(Request $request): View|RedirectResponse
    {
        if (! $this->streams->isEnabled(RevenueStream::ProSubscription)) {
            return redirect()->route('home');
        }

        $user = $request->user();

        return view('monetization::plans', [
            'plans' => $this->plans->active(),
            'subscription' => $user === null ? null : $this->subscriptions->ownSubscription($user),
            'hasAccess' => $user !== null && $this->subscriptions->hasAccess($user),
        ]);
    }
}
