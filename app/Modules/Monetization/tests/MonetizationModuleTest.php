<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Tests;

use App\Support\Modules\ModuleRegistry;
use Tests\TestCase;

final class MonetizationModuleTest extends TestCase
{
    public function test_module_is_enabled(): void
    {
        $this->assertTrue(
            $this->app->make(ModuleRegistry::class)->isEnabled('Monetization'),
        );
    }
}
