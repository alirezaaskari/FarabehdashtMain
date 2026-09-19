<?php

declare(strict_types=1);

namespace App\Modules\Core\Providers;

use App\Contracts\AuditableEvent;
use App\Contracts\AuditTrail;
use App\Modules\Core\Listeners\RecordAuditableEvent;
use App\Modules\Core\Seo\SitemapBuilder;
use App\Modules\Core\Services\AuditRecorder;
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
    /** برچسب کانتینر برای ماژول‌هایی که نشانی به نقشه سایت می‌دهند. */
    public const SITEMAP_SOURCES = 'sitemap.sources';

    public function moduleName(): string
    {
        return 'Core';
    }

    protected function registerModule(): void
    {
        $this->app->singleton(AuditTrail::class, AuditRecorder::class);

        $this->app->singleton(SettingsRepository::class);

        $this->app->singleton(TaxonomyRegistry::class, fn (): TaxonomyRegistry => new TaxonomyRegistry(
            (array) config('core.taxonomies', []),
        ));

        $this->app->singleton(SitemapBuilder::class, fn (): SitemapBuilder => new SitemapBuilder(
            $this->app->tagged(self::SITEMAP_SOURCES),
        ));

        // تا وقتی هیچ ماژول محتوایی نیامده، برچسب باید وجود داشته باشد وگرنه
        // tagged() روی برچسب ناشناخته آرایه خالی برمی‌گرداند و این درست است،
        // ولی صریح‌بودنش بعداً وقت کمتری می‌گیرد.
        $this->app->tag([], self::SITEMAP_SOURCES);
    }

    protected function bootModule(): void
    {
        // روی خود قرارداد ثبت می‌شود، نه تک‌تک رویدادها: ماژول تازه برای
        // ثبت‌شدن در دفتر رویداد هیچ سیم‌کشی اضافه‌ای لازم ندارد.
        Event::listen(AuditableEvent::class, RecordAuditableEvent::class);
    }

    /** @return list<class-string> */
    public function provides(): array
    {
        return [AuditTrail::class, SettingsRepository::class, TaxonomyRegistry::class, SitemapBuilder::class];
    }
}
