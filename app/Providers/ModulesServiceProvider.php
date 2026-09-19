<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Modules\ModuleNotFoundException;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\ServiceProvider;

/**
 * ماژول‌های فعال را طبق config/modules.php ثبت می‌کند.
 *
 * اگر ماژولی در فهرست باشد اما ServiceProviderش وجود نداشته باشد، برنامه با
 * پیام روشن می‌ایستد. رد کردن بی‌صدا ممنوع است؛ پیکربندی اشتباه باید بلند باشد.
 */
final class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ModuleRegistry::class,
            static fn ($app): ModuleRegistry => new ModuleRegistry($app->make(Config::class)),
        );

        $registry = $this->app->make(ModuleRegistry::class);

        foreach ($registry->enabled() as $module) {
            $provider = $registry->providerClass($module);

            if (! class_exists($provider)) {
                throw ModuleNotFoundException::forModule($module, $provider);
            }

            $this->app->register($provider);
        }
    }
}
