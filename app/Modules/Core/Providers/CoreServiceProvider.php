<?php

declare(strict_types=1);

namespace App\Modules\Core\Providers;

use App\Contracts\AuditableEvent;
use App\Contracts\AuditTrail;
use App\Contracts\AuditTrailReader;
use App\Contracts\RevisionEvent;
use App\Contracts\SettingsStore;
use App\Contracts\SitemapSource;
use App\Modules\Core\Listeners\RecordAuditableEvent;
use App\Modules\Core\Listeners\RecordRevisionEvent;
use App\Modules\Core\Seo\HomeSitemapSource;
use App\Modules\Core\Seo\SitemapBuilder;
use App\Modules\Core\Services\AuditReader;
use App\Modules\Core\Services\AuditRecorder;
use App\Modules\Core\Services\HomePage;
use App\Modules\Core\Services\SettingsRepository;
use App\Modules\Core\Services\TaxonomyRegistry;
use App\Support\Modules\ModuleProvider;
use Illuminate\Support\Facades\Event;

/**
 * ماژول پایه.
 *
 * بقیه ماژول‌ها فقط از راه قراردادهای `app/Contracts` و رویدادها با این ماژول
 * حرف می‌زنند؛ هیچ ماژولی کلاس‌های `App\Modules\Core\Domain` را import نمی‌کند.
 * به همین دلیل Core در `config/modules.php` بالاتر از بقیه می‌آید.
 */
final class CoreServiceProvider extends ModuleProvider
{
    /** برچسب کانتینر برای ماژول‌هایی که بخشی روی صفحه اصلی دارند. */
    public const HOMEPAGE_SOURCES = 'home.sources';

    public function moduleName(): string
    {
        return 'Core';
    }

    protected function registerModule(): void
    {
        $this->app->singleton(AuditTrail::class, AuditRecorder::class);
        $this->app->singleton(AuditTrailReader::class, AuditReader::class);

        $this->app->singleton(SettingsRepository::class);
        $this->app->alias(SettingsRepository::class, SettingsStore::class);

        $this->app->singleton(TaxonomyRegistry::class, fn (): TaxonomyRegistry => new TaxonomyRegistry(
            (array) config('core.taxonomies', []),
        ));

        $this->app->singleton(SitemapBuilder::class, fn (): SitemapBuilder => new SitemapBuilder(
            $this->app->tagged(SitemapSource::TAG),
        ));

        // صفحه اصلی مال Core است؛ بقیه بخش‌ها را ماژول‌ها با همین برچسب می‌آورند.
        $this->app->tag([HomeSitemapSource::class], SitemapSource::TAG);

        $this->app->singleton(HomePage::class, fn (): HomePage => new HomePage(
            $this->app->tagged(self::HOMEPAGE_SOURCES),
        ));

        $this->app->tag([], self::HOMEPAGE_SOURCES);
    }

    protected function bootModule(): void
    {
        // روی خود قرارداد ثبت می‌شود، نه تک‌تک رویدادها: ماژول تازه برای
        // ثبت‌شدن در دفتر رویداد هیچ سیم‌کشی اضافه‌ای لازم ندارد.
        Event::listen(AuditableEvent::class, RecordAuditableEvent::class);
        Event::listen(RevisionEvent::class, RecordRevisionEvent::class);
    }

    /** @return list<class-string> */
    public function provides(): array
    {
        return [
            AuditTrail::class,
            AuditTrailReader::class,
            SettingsRepository::class,
            TaxonomyRegistry::class,
            SitemapBuilder::class,
            HomePage::class,
        ];
    }
}
