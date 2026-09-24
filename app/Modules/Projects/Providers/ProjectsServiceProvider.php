<?php

declare(strict_types=1);

namespace App\Modules\Projects\Providers;

use App\Contracts\WorkspaceWidgetSource;
use App\Modules\Monetization\Providers\MonetizationServiceProvider;
use App\Modules\Projects\Services\IndustryTemplates;
use App\Modules\Projects\Services\ProjectQuota;
use App\Modules\Projects\Workspace\ActiveProjects;
use App\Support\Modules\ModuleProvider;

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
    }
}
