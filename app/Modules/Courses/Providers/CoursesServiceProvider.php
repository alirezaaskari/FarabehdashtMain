<?php

declare(strict_types=1);

namespace App\Modules\Courses\Providers;

use App\Modules\Admin\Providers\AdminServiceProvider;
use App\Modules\Core\Providers\CoreServiceProvider;
use App\Modules\Courses\Admin\PendingCourses;
use App\Modules\Courses\Home\CourseHighlights;
use App\Support\Modules\ModuleProvider;

final class CoursesServiceProvider extends ModuleProvider
{
    public function moduleName(): string
    {
        return 'Courses';
    }

    protected function registerModule(): void
    {
        $this->app->tag([PendingCourses::class], AdminServiceProvider::APPROVAL_SOURCES);

        $this->app->tag([CourseHighlights::class], CoreServiceProvider::HOMEPAGE_SOURCES);
    }
}
