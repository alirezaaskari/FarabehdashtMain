<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Tests;

use App\Support\Modules\ModuleRegistry;
use Tests\TestCase;

final class MarketplaceModuleTest extends TestCase
{
    public function test_module_is_enabled(): void
    {
        $this->assertTrue(
            $this->app->make(ModuleRegistry::class)->isEnabled('Marketplace'),
        );
    }
}
