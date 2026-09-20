<?php

declare(strict_types=1);

namespace App\Modules\Tools\Providers;

use App\Contracts\CalculationReader;
use App\Modules\Tools\Console\SyncToolsCommand;
use App\Modules\Tools\Services\ResultPresenter;
use App\Modules\Tools\Services\SavedCalculationReader;
use App\Modules\Tools\Services\ToolAdvisor;
use App\Modules\Tools\Services\ToolCatalog;
use App\Support\Modules\ModuleProvider;
use Farabehdasht\CalcEngine\Engine;
use Farabehdasht\CalcEngine\FormulaRegistry;
use Farabehdasht\CalcEngine\Formulas\DefaultFormulas;

/**
 * ماژول ابزارها.
 *
 * موتور محاسبات یک پکیج است، نه یک ماژول، پس اتصالش این‌جاست و نه در هسته
 * برنامه: اگر این ماژول برداشته شود، موتور هم دیگر در کانتینر نمی‌نشیند.
 *
 * رجیستری جداگانه از موتور ثبت می‌شود تا تست‌ها بتوانند فقط رجیستری را عوض
 * کنند (مثلاً برای افزودن یک نسخه دوم ساختگی) بی‌آنکه موتور را بازنویسی کنند.
 */
final class ToolsServiceProvider extends ModuleProvider
{
    public function moduleName(): string
    {
        return 'Tools';
    }

    protected function registerModule(): void
    {
        $this->app->singleton(FormulaRegistry::class, static fn (): FormulaRegistry => DefaultFormulas::registry());

        $this->app->singleton(Engine::class, fn (): Engine => new Engine(
            $this->app->make(FormulaRegistry::class),
        ));

        $this->app->singleton(ToolCatalog::class, fn (): ToolCatalog => new ToolCatalog(
            $this->app->make(FormulaRegistry::class),
            (array) config('tools', []),
        ));

        $this->app->singleton(ResultPresenter::class, static fn (): ResultPresenter => new ResultPresenter(
            (array) config('tools.output_labels', []),
        ));

        // تنها دری که ماژول‌های دیگر از آن به محاسبه‌های ذخیره‌شده نگاه
        // می‌کنند. اگر این ماژول خاموش باشد، قرارداد بسته نمی‌شود و
        // مصرف‌کننده‌ها باید با نبودنش کنار بیایند.
        $this->app->singleton(CalculationReader::class, SavedCalculationReader::class);

        $this->app->singleton(ToolAdvisor::class, fn (): ToolAdvisor => new ToolAdvisor(
            $this->app->make(ToolCatalog::class),
            (array) config('tools.advisor', []),
        ));
    }

    protected function bootModule(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([SyncToolsCommand::class]);
        }
    }
}
