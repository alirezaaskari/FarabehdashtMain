<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Http\Controllers;

use App\Contracts\EntitlementGate;
use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Monetization\Services\PlanCatalog;
use App\Modules\Monetization\Services\StreamRegistry;
use App\Support\Entitlement\Feature;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * گذرگاه تبدیل رایگان به Pro.
 *
 * مقصد هر ردی که لایه دسترسی داده. شناسه امکان را از نشانی می‌گیرد و دوباره
 * از همان دروازه می‌پرسد، پس متن صفحه دقیقاً همان چیزی است که کاربر خورده:
 * «۵ از ۵ محاسبه» با «پروژه برای مشترکان است» دو صفحه‌اند.
 *
 * دوباره‌پرسیدن عمدی است و کش‌کردن تصمیم در نشانی نیست: کاربری که در تب
 * دیگری مشترک شده، با تازه‌کردن همین صفحه باید ببیند که دیگر رد نمی‌شود.
 */
final readonly class UpgradeController
{
    public function __construct(
        private EntitlementGate $gate,
        private PlanCatalog $plans,
        private StreamRegistry $streams,
    ) {}

    public function __invoke(Request $request): View|RedirectResponse
    {
        if (! $this->streams->isEnabled(RevenueStream::ProSubscription)) {
            return redirect()->route('home');
        }

        $feature = Feature::tryFrom((string) $request->query('feature', ''));

        return view('monetization::upgrade', [
            'feature' => $feature,
            'decision' => $feature === null ? null : $this->gate->decide($request->user(), $feature),
            'plans' => $this->plans->active(),
        ]);
    }
}
