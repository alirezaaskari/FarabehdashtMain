<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Tests;

use App\Support\Modules\ModuleRegistry;
use Tests\TestCase;

final class ConsultingModuleTest extends TestCase
{
    public function test_module_is_enabled(): void
    {
        $this->assertTrue(
            $this->app->make(ModuleRegistry::class)->isEnabled('Consulting'),
        );
    }
}
