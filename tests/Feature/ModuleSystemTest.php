<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Health\Providers\HealthServiceProvider;
use App\Providers\ModulesServiceProvider;
use App\Support\Modules\ModuleNotFoundException;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Tests\TestCase;

final class ModuleSystemTest extends TestCase
{
    public function test_a_module_listed_without_a_provider_fails_loudly(): void
    {
        $original = Container::getInstance();

        try {
            $app = new Application(base_path());
            $app->instance('config', new Repository([
                'modules' => [
                    'enabled' => ['MissingModule'],
                    'path' => 'app/Modules',
                    'namespace' => 'App\\Modules',
                ],
            ]));

            $this->expectException(ModuleNotFoundException::class);

            (new ModulesServiceProvider($app))->register();
        } finally {
            Container::setInstance($original);
        }
    }

    public function test_the_failure_message_names_the_module_and_the_expected_provider(): void
    {
        $exception = ModuleNotFoundException::forModule(
            'MissingModule',
            'App\Modules\MissingModule\Providers\MissingModuleServiceProvider',
        );

        $this->assertStringContainsString('MissingModule', $exception->getMessage());
        $this->assertStringContainsString('fbh:make-module', $exception->getMessage());
    }

    public function test_module_key_is_snake_case_of_the_module_name(): void
    {
        $provider = new HealthServiceProvider($this->app);

        $this->assertSame('health', $provider->moduleKey());
    }

    public function test_registry_resolves_module_paths_under_the_configured_root(): void
    {
        $registry = $this->app->make(ModuleRegistry::class);

        $this->assertSame(base_path('app/Modules/Health'), $registry->path('Health'));
        $this->assertSame(
            base_path('app/Modules/Health/routes/web.php'),
            $registry->path('Health', 'routes/web.php'),
        );
        $this->assertSame('app/Modules', $registry->rootPath());
        $this->assertSame('App\Modules', $registry->rootNamespace());
    }

    public function test_only_enabled_modules_are_reported(): void
    {
        $registry = $this->app->make(ModuleRegistry::class);

        $this->assertContains('Health', $registry->enabled());
        // نامی که عمداً هرگز ماژول نمی‌شود؛ نام یک ماژول برنامه‌ریزی‌شده
        // این‌جا یعنی تست با رسیدن آن بخش می‌شکند.
        $this->assertFalse($registry->isEnabled('NotAModule'));
    }
}
