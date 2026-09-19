<?php

declare(strict_types=1);

namespace App\Modules\Core\Tests;

use App\Support\Modules\ModuleRegistry;
use Tests\TestCase;

final class CoreModuleTest extends TestCase
{
    public function test_module_is_enabled(): void
    {
        $this->assertTrue(
            $this->app->make(ModuleRegistry::class)->isEnabled('Core'),
        );
    }

    public function test_core_is_registered_before_the_modules_that_rely_on_it(): void
    {
        $enabled = $this->app->make(ModuleRegistry::class)->enabled();

        $this->assertSame('Core', $enabled[0], 'ماژول پایه باید اول ثبت شود.');
    }
}
