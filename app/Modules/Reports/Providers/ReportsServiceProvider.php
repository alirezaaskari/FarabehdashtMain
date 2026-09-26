<?php

declare(strict_types=1);

namespace App\Modules\Reports\Providers;

use App\Contracts\CalculationReferences;
use App\Contracts\EntitlementGate;
use App\Contracts\ReportSource;
use App\Contracts\SalesSwitch;
use App\Contracts\WorkspaceWidgetSource;
use App\Modules\Reports\Actions\IssueReport;
use App\Modules\Reports\Services\ReportCalculationReferences;
use App\Modules\Reports\Services\ReportPdf;
use App\Modules\Reports\Services\ReportSale;
use App\Modules\Reports\Services\ReportSources;
use App\Modules\Reports\Workspace\RecentReports;
use App\Support\Modules\ModuleProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

final class ReportsServiceProvider extends ModuleProvider
{
    public const VERIFY_LIMITER = 'reports.verify';

    public function moduleName(): string
    {
        return 'Reports';
    }

    protected function registerModule(): void
    {
        // برچسب خالی تا وقتی هیچ منبعی ثبت نشده، `tagged` باز هم کار کند.
        $this->app->tag([], ReportSource::TAG);

        $this->app->singleton(ReportSources::class, fn (): ReportSources => new ReportSources(
            $this->app->tagged(ReportSource::TAG),
        ));

        $this->app->singleton(ReportPdf::class, fn (): ReportPdf => new ReportPdf(
            $this->app->make('view'),
            $this->modulePath('resources/fonts'),
            $this->modulePath('resources/pdf/report.css'),
            storage_path('framework/cache/mpdf'),
        ));

        $this->app->singleton(ReportSale::class, fn (): ReportSale => new ReportSale(
            $this->app,
            $this->app->make(SalesSwitch::class),
            (int) config('reports.single_price_toman', 49_000),
        ));

        $this->app->bind(IssueReport::class, fn (): IssueReport => new IssueReport(
            $this->app->make(ReportSources::class),
            $this->app->make(ReportPdf::class),
            $this->app->make(EntitlementGate::class),
            $this->app->make(ReportSale::class),
            $this->app->make(DatabaseManager::class),
            $this->app->make(Dispatcher::class),
            $this->app->make(FilesystemManager::class)->disk((string) config('reports.disk', 'local')),
            (string) config('reports.directory', 'reports'),
        ));

        $this->app->tag([RecentReports::class], WorkspaceWidgetSource::TAG);
        $this->app->tag([ReportCalculationReferences::class], CalculationReferences::TAG);
    }

    protected function bootModule(): void
    {
        RateLimiter::for(self::VERIFY_LIMITER, static fn (Request $request): Limit => Limit::perMinute(
            (int) config('reports.verify_per_minute', 20),
        )->by((string) $request->ip()));
    }
}
