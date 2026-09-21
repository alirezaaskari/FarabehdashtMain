<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Providers;

use App\Support\Modules\ModuleProvider;

final class CommerceServiceProvider extends ModuleProvider
{
    public function moduleName(): string
    {
        return 'Commerce';
    }
}
