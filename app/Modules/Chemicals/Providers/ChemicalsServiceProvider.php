<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Providers;

use App\Contracts\LinkTargetSource;
use App\Contracts\SearchSource;
use App\Contracts\SitemapSource;
use App\Modules\Admin\Providers\AdminServiceProvider;
use App\Modules\Chemicals\Admin\PendingSubstances;
use App\Modules\Chemicals\Console\ImportSubstancesCommand;
use App\Modules\Chemicals\Console\SeedChemicalsCommand;
use App\Modules\Chemicals\Home\SubstanceHighlights;
use App\Modules\Chemicals\Linking\SubstanceLinks;
use App\Modules\Chemicals\Search\SubstanceSearch;
use App\Modules\Chemicals\Seo\SubstanceSitemapSource;
use App\Modules\Chemicals\Services\CsvExporter;
use App\Modules\Chemicals\Services\CsvImporter;
use App\Modules\Chemicals\Services\RelatedTools;
use App\Modules\Core\Providers\CoreServiceProvider;
use App\Support\Modules\ModuleProvider;

/**
 * ماژول بانک مواد شیمیایی.
 *
 * دو اتصال بیرونی، هر دو از راه قرارداد و برچسب کانتینر: نقشه سایت (Core) و
 * صف تأیید (Admin). اگر آن ماژول‌ها نباشند، برچسبشان هم نیست و این ماژول
 * بی‌سروصدا بدون آن‌ها کار می‌کند.
 */
final class ChemicalsServiceProvider extends ModuleProvider
{
    public function moduleName(): string
    {
        return 'Chemicals';
    }

    protected function registerModule(): void
    {
        $this->app->singleton(CsvImporter::class, static fn (): CsvImporter => new CsvImporter(
            (array) config('chemicals.csv.columns', []),
            (int) config('chemicals.csv.max_rows', 500),
        ));

        $this->app->singleton(CsvExporter::class, static fn (): CsvExporter => new CsvExporter(
            (array) config('chemicals.csv.columns', []),
        ));

        $this->app->singleton(RelatedTools::class, fn (): RelatedTools => new RelatedTools(
            $this->app,
            (array) config('chemicals.related_tools', []),
        ));

        $this->app->tag([SubstanceSitemapSource::class], SitemapSource::TAG);
        $this->app->tag([PendingSubstances::class], AdminServiceProvider::APPROVAL_SOURCES);

        $this->app->tag([SubstanceHighlights::class], CoreServiceProvider::HOMEPAGE_SOURCES);

        $this->app->tag([SubstanceSearch::class], SearchSource::TAG);
        $this->app->tag([SubstanceLinks::class], LinkTargetSource::TAG);
    }

    protected function bootModule(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([SeedChemicalsCommand::class, ImportSubstancesCommand::class]);
        }
    }
}
