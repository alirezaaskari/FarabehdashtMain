<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Providers;

use App\Contracts\CommissionCalculator;
use App\Contracts\SearchSource;
use App\Contracts\SitemapSource;
use App\Contracts\WorkspaceWidgetSource;
use App\Modules\Admin\Providers\AdminServiceProvider;
use App\Modules\Commerce\Admin\PendingProducts;
use App\Modules\Commerce\Home\ProductHighlights;
use App\Modules\Commerce\Search\ProductSearch;
use App\Modules\Commerce\Seo\ProductSitemapSource;
use App\Modules\Commerce\Services\CommissionService;
use App\Modules\Commerce\Workspace\CommerceWidgets;
use App\Modules\Core\Providers\CoreServiceProvider;
use App\Support\Modules\ModuleProvider;

/**
 * ماژول تجارت.
 *
 * `CommissionCalculator` دری است که ماژول‌های دیگر (مثل دوره‌ها، بخش ۱۳) برای
 * کمیسیون از آن عبور می‌کنند. درگاه پرداخت این‌جا ثبت نمی‌شود: زیرساخت مشترک
 * است و در `AppServiceProvider` می‌نشیند (`config/payments.php`).
 */
final class CommerceServiceProvider extends ModuleProvider
{
    public function moduleName(): string
    {
        return 'Commerce';
    }

    protected function registerModule(): void
    {
        $this->app->singleton(CommissionCalculator::class, CommissionService::class);

        $this->app->tag([PendingProducts::class], AdminServiceProvider::APPROVAL_SOURCES);

        $this->app->tag([ProductHighlights::class], CoreServiceProvider::HOMEPAGE_SOURCES);

        // برچسب‌ها روی خود قراردادها هستند؛ حذف ماژول میزکار این ماژول را نمی‌شکند.
        $this->app->tag([ProductSearch::class], SearchSource::TAG);
        $this->app->tag([ProductSitemapSource::class], SitemapSource::TAG);
        $this->app->tag([CommerceWidgets::class], WorkspaceWidgetSource::TAG);
    }
}
