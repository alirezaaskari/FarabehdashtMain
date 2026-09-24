<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Providers;

use App\Contracts\SearchSource;
use App\Modules\Admin\Providers\AdminServiceProvider;
use App\Modules\Core\Providers\CoreServiceProvider;
use App\Modules\Encyclopedia\Admin\PendingArticles;
use App\Modules\Encyclopedia\Console\SeedEncyclopediaCommand;
use App\Modules\Encyclopedia\Home\ArticleHighlights;
use App\Modules\Encyclopedia\Search\ArticleSearch;
use App\Modules\Encyclopedia\Seo\ArticleSitemapSource;
use App\Modules\Encyclopedia\Services\ContentHealth;
use App\Modules\Encyclopedia\Services\CrossLinks;
use App\Modules\Encyclopedia\Services\Freshness;
use App\Modules\Encyclopedia\Services\ReviewReminder;
use App\Support\Modules\ModuleProvider;

/**
 * ماژول دانشنامه.
 *
 * دو اتصال بیرونی دارد و هر دو از راه قرارداد و برچسب کانتینر است، نه import
 * مدل: نقشه سایت (Core) و صف تأیید (Admin). اگر هرکدام از آن ماژول‌ها نباشند،
 * برچسبشان هم نیست و دانشنامه بی‌سروصدا بدون آن‌ها کار می‌کند.
 */
final class EncyclopediaServiceProvider extends ModuleProvider
{
    public function moduleName(): string
    {
        return 'Encyclopedia';
    }

    protected function registerModule(): void
    {
        $this->app->singleton(Freshness::class, static fn (): Freshness => new Freshness(
            (int) config('encyclopedia.freshness.warning_days', 60),
        ));

        $this->app->singleton(ContentHealth::class, fn (): ContentHealth => new ContentHealth(
            $this->app->make(Freshness::class),
            (array) config('encyclopedia.health.weights', []),
            (int) config('encyclopedia.health.minimum_sections', 3),
        ));

        $this->app->singleton(CrossLinks::class, fn (): CrossLinks => new CrossLinks(
            $this->app,
            (int) config('encyclopedia.cross_links.max', 4),
        ));

        // افق یادآور همان آستانه هشدار تازگی است: دو عدد برای یک مفهوم،
        // روزی از هم جدا می‌افتند و مدیر دو حقیقت متفاوت می‌بیند.
        $this->app->singleton(ReviewReminder::class, static fn (): ReviewReminder => new ReviewReminder(
            (int) config('encyclopedia.freshness.warning_days', 60),
        ));

        $this->app->tag([ArticleSitemapSource::class], CoreServiceProvider::SITEMAP_SOURCES);
        $this->app->tag([PendingArticles::class], AdminServiceProvider::APPROVAL_SOURCES);

        $this->app->tag([ArticleHighlights::class], CoreServiceProvider::HOMEPAGE_SOURCES);

        $this->app->tag([ArticleSearch::class], SearchSource::TAG);
    }

    protected function bootModule(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([SeedEncyclopediaCommand::class]);
        }
    }
}
