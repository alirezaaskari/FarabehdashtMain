<?php

declare(strict_types=1);

namespace App\Modules\Health\Tests;

use App\Support\Modules\ModuleRegistry;
use Tests\TestCase;

final class HealthCheckTest extends TestCase
{
    public function test_health_endpoint_reports_ok_and_lists_enabled_modules(): void
    {
        $this->getJson('/_health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('modules.0', 'Health');
    }

    public function test_module_is_registered_in_the_registry(): void
    {
        $registry = $this->app->make(ModuleRegistry::class);

        $this->assertTrue($registry->isEnabled('Health'));
        $this->assertSame(
            'App\Modules\Health\Providers\HealthServiceProvider',
            $registry->providerClass('Health'),
        );
    }

    public function test_module_routes_and_views_resolve_from_the_module_folder(): void
    {
        $registry = $this->app->make(ModuleRegistry::class);

        $this->assertFileExists($registry->path('Health', 'routes/web.php'));
        $this->assertFileExists($registry->path('Health', 'README.md'));
    }
}
