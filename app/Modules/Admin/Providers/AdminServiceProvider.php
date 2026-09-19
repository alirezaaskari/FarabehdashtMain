<?php

declare(strict_types=1);

namespace App\Modules\Admin\Providers;

use App\Support\Modules\ModuleProvider;

final class AdminServiceProvider extends ModuleProvider
{
    public function moduleName(): string
    {
        return 'Admin';
    }
}
