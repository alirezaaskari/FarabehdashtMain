<?php

declare(strict_types=1);

namespace App\Modules\Projects\Providers;

use App\Modules\Projects\Services\IndustryTemplates;
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
    }
}
