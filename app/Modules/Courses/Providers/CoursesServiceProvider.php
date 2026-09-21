<?php

declare(strict_types=1);

namespace App\Modules\Courses\Providers;

use App\Support\Modules\ModuleProvider;

final class CoursesServiceProvider extends ModuleProvider
{
    public function moduleName(): string
    {
        return 'Courses';
    }
}
