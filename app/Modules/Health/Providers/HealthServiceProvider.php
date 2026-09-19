<?php

declare(strict_types=1);

namespace App\Modules\Health\Providers;

use App\Support\Modules\ModuleProvider;

final class HealthServiceProvider extends ModuleProvider
{
    public function moduleName(): string
    {
        return 'Health';
    }
}
