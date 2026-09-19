<?php

declare(strict_types=1);

namespace App\Support\Modules;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * کلاس پایه ServiceProvider هر ماژول.
 *
 * مسیرهای قراردادی را خودکار سیم‌کشی می‌کند تا ماژول‌ها کد تکراری نداشته باشند:
 *
 *   routes/web.php          → مسیرهای وب ماژول، با میان‌افزار گروه web
 *   routes/admin.php        → مسیرهای پنل مدیریت، با گروه web و پیشوند admin
 *   database/migrations     → مهاجرت‌های ماژول
 *   resources/views         → قالب‌ها، با فضای‌نام کوچک‌شده نام ماژول
 *   lang                    → ترجمه‌ها، با همان فضای‌نام
 *   config/<module>.php     → پیکربندی ماژول، با کلید کوچک‌شده نام ماژول
 *
 * هر فایل یا پوشه‌ای که وجود نداشته باشد، بی‌سروصدا رد می‌شود.
 */
abstract class ModuleProvider extends ServiceProvider
{
    /**
     * نام ماژول، دقیقاً همان‌طور که در config/modules.php نوشته شده.
     */
    abstract public function moduleName(): string;

    public function register(): void
    {
        $config = $this->modulePath('config/'.$this->moduleKey().'.php');

        if (is_file($config)) {
            $this->mergeConfigFrom($config, $this->moduleKey());
        }

        $this->registerModule();
    }

    public function boot(): void
    {
        $this->bootRoutes();
        $this->bootViews();
        $this->bootTranslations();
        $this->bootMigrations();

        $this->bootModule();
    }

    /**
     * جای ثبت بایندینگ‌های اختصاصی ماژول.
     */
    protected function registerModule(): void {}

    /**
     * جای راه‌اندازی اختصاصی ماژول: رویدادها، Policyها، Gateها.
     */
    protected function bootModule(): void {}

    /**
     * کلید کوچک‌شده ماژول: فضای‌نام قالب‌ها، ترجمه‌ها و پیکربندی.
     */
    final public function moduleKey(): string
    {
        return str($this->moduleName())->snake()->toString();
    }

    final protected function modulePath(string $sub = ''): string
    {
        return $this->registry()->path($this->moduleName(), $sub);
    }

    private function registry(): ModuleRegistry
    {
        return $this->app->make(ModuleRegistry::class);
    }

    private function bootRoutes(): void
    {
        $web = $this->modulePath('routes/web.php');

        if (is_file($web)) {
            Route::middleware('web')->group($web);
        }

        $admin = $this->modulePath('routes/admin.php');

        if (is_file($admin)) {
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group($admin);
        }
    }

    private function bootViews(): void
    {
        $views = $this->modulePath('resources/views');

        if (is_dir($views)) {
            $this->loadViewsFrom($views, $this->moduleKey());
        }
    }

    private function bootTranslations(): void
    {
        $lang = $this->modulePath('lang');

        if (is_dir($lang)) {
            $this->loadTranslationsFrom($lang, $this->moduleKey());
        }
    }

    private function bootMigrations(): void
    {
        $migrations = $this->modulePath('database/migrations');

        if (is_dir($migrations)) {
            $this->loadMigrationsFrom($migrations);
        }
    }
}
