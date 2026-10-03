<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Providers;

use App\Contracts\QuickActionSource;
use App\Contracts\SitemapSource;
use App\Contracts\TunableSource;
use App\Modules\Admin\Providers\AdminServiceProvider;
use App\Modules\Marketplace\Admin\PendingMarketItems;
use App\Modules\Marketplace\QuickActions\MarketQuickActions;
use App\Modules\Marketplace\Seo\MarketSitemapSource;
use App\Modules\Marketplace\Settings\MarketTunables;
use App\Support\Modules\ModuleProvider;

/**
 * بازار پروژه (بخش ۲۱، نسخه ۴): کارفرما پروژه تعریف می‌کند، مشاور و
 * آزمایشگاه تأییدشده پیشنهاد می‌دهند و پول هر مرحله در امانت پروژه می‌ماند.
 *
 * خدمت‌ها و ارائه‌دهنده‌ها از قرارداد `ServiceProviderDirectory` می‌آیند و
 * امانت از `EscrowKeeper`؛ هیچ مدلی از ماژول دیگر import نمی‌شود.
 */
final class MarketplaceServiceProvider extends ModuleProvider
{
    public function moduleName(): string
    {
        return 'Marketplace';
    }

    protected function registerModule(): void
    {
        $this->app->tag([MarketTunables::class], TunableSource::TAG);
        $this->app->tag([MarketSitemapSource::class], SitemapSource::TAG);
        $this->app->tag([PendingMarketItems::class], AdminServiceProvider::APPROVAL_SOURCES);
        $this->app->tag([MarketQuickActions::class], QuickActionSource::TAG);
    }
}
