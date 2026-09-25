<?php

declare(strict_types=1);

namespace App\Modules\Projects\Providers;

use App\Contracts\CalculationReferences;
use App\Contracts\ReportSource;
use App\Contracts\WorkspaceWidgetSource;
use App\Modules\Monetization\Providers\MonetizationServiceProvider;
use App\Modules\Projects\Console\RemindCalibrationsCommand;
use App\Modules\Projects\Reports\ProjectReportSource;
use App\Modules\Projects\Services\IndustryTemplates;
use App\Modules\Projects\Services\ProjectQuota;
use App\Modules\Projects\Services\ReadingCalculationReferences;
use App\Modules\Projects\Workspace\ActiveProjects;
use App\Support\Modules\ModuleProvider;
use Illuminate\Console\Scheduling\Schedule;

final class ProjectsServiceProvider extends ModuleProvider
{
    public function moduleName(): string
    {
        return 'Projects';
    }

    protected function registerModule(): void
    {
        $this->app->singleton(IndustryTemplates::class, static fn (): IndustryTemplates => new IndustryTemplates(
            (array) config('projects.templates', []),
        ));

        // سقف پلن رایگان را ماژول درآمدزایی اجرا می‌کند، ولی شمردن کار
        // صاحب داده است (قاعده ۱).
        $this->app->tag([ProjectQuota::class], MonetizationServiceProvider::QUOTA_COUNTERS);

        $this->app->tag([ActiveProjects::class], WorkspaceWidgetSource::TAG);
        $this->app->tag([ProjectReportSource::class], ReportSource::TAG);
        $this->app->tag([ReadingCalculationReferences::class], CalculationReferences::TAG);
    }

    protected function bootModule(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([RemindCalibrationsCommand::class]);
        }

        // ساعت ۹ تا پیامکش پشت ساعت سکوت (۲۲ تا ۸) نماند.
        $this->callAfterResolving(Schedule::class, static function (Schedule $schedule): void {
            $schedule->command(RemindCalibrationsCommand::class)->dailyAt('09:00');
        });
    }
}
