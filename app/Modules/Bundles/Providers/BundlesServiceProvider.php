<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Providers;

use App\Contracts\BundleComponentSource;
use App\Contracts\CommissionCalculator;
use App\Contracts\SitemapSource;
use App\Modules\Bundles\Seo\BundleSitemapSource;
use App\Modules\Bundles\Services\ComponentCatalog;
use App\Modules\Bundles\Services\PriceSplitter;
use App\Support\Modules\ModuleProvider;

/**
 * بسته‌های راه‌حل (بخش ۱۸-۸).
 *
 * اجزا را ماژول صاحبشان با برچسب `BundleComponentSource::TAG` می‌دهد و این
 * ماژول هیچ مدلی از آن‌ها نمی‌شناسد. کمیسیون از `CommissionCalculator`
 * می‌آید؛ اگر فروشگاه خاموش باشد، سهم صاحب جزء صفر و همه درآمد پلتفرم است.
 */
final class BundlesServiceProvider extends ModuleProvider
{
    public function moduleName(): string
    {
        return 'Bundles';
    }

    protected function registerModule(): void
    {
        $this->app->tag([], BundleComponentSource::TAG);

        $this->app->singleton(ComponentCatalog::class, static fn ($app): ComponentCatalog => new ComponentCatalog(
            $app->tagged(BundleComponentSource::TAG),
        ));

        $this->app->bind(PriceSplitter::class, static fn ($app): PriceSplitter => new PriceSplitter(
            $app->bound(CommissionCalculator::class) ? $app->make(CommissionCalculator::class) : null,
        ));

        $this->app->tag([BundleSitemapSource::class], SitemapSource::TAG);
    }
}
