<?php

declare(strict_types=1);

namespace App\Modules\Tools\Providers;

use App\Contracts\CalculationReader;
use App\Contracts\CalculationReferences;
use App\Contracts\LinkTargetSource;
use App\Contracts\ReportSource;
use App\Contracts\SearchSource;
use App\Contracts\SitemapSource;
use App\Contracts\ToolDirectory;
use App\Contracts\WorkspaceWidgetSource;
use App\Modules\Core\Providers\CoreServiceProvider;
use App\Modules\Monetization\Providers\MonetizationServiceProvider;
use App\Modules\Tools\Actions\DeleteSavedCalculation;
use App\Modules\Tools\Console\SyncToolsCommand;
use App\Modules\Tools\Domain\ResolvedTool;
use App\Modules\Tools\Home\ToolHighlights;
use App\Modules\Tools\Linking\ToolLinks;
use App\Modules\Tools\Reports\CalculationReportSource;
use App\Modules\Tools\Search\ToolSearch;
use App\Modules\Tools\Seo\ToolSitemapSource;
use App\Modules\Tools\Services\ResultPresenter;
use App\Modules\Tools\Services\SavedCalculationQuota;
use App\Modules\Tools\Services\SavedCalculationReader;
use App\Modules\Tools\Services\ToolAdvisor;
use App\Modules\Tools\Services\ToolCatalog;
use App\Modules\Tools\Services\ToolTitleDirectory;
use App\Modules\Tools\Workspace\RecentCalculations;
use App\Support\Modules\ModuleProvider;
use Farabehdasht\CalcEngine\Engine;
use Farabehdasht\CalcEngine\FormulaRegistry;
use Farabehdasht\CalcEngine\Formulas\DefaultFormulas;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\View;

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

        // ماژول‌هایی که شناسه محاسبه نگه می‌دارند (پروژه، گزارش) برچسب
        // می‌زنند؛ برچسب خالی تا بدون آن‌ها هم `tagged` کار کند.
        $this->app->tag([], CalculationReferences::TAG);
        $this->app->bind(DeleteSavedCalculation::class, fn (): DeleteSavedCalculation => new DeleteSavedCalculation(
            $this->app->tagged(CalculationReferences::TAG),
            $this->app->make(Dispatcher::class),
        ));

        // همان مرز، برای عنوان خوانای ابزار در قالب صنعتی پروژه‌ها.
        $this->app->singleton(ToolDirectory::class, ToolTitleDirectory::class);

        // مقاله، فایل و دوره پیشنهادی از جست‌وجوی ماژول‌های دیگر می‌آید؛ همان
        // برچسب قرارداد، پس ماژول خاموش فقط ردیف‌هایش را کم می‌کند.
        $this->app->singleton(ToolAdvisor::class, fn (): ToolAdvisor => new ToolAdvisor(
            $this->app->make(ToolCatalog::class),
            (array) config('tools.advisor', []),
            $this->app->tagged(SearchSource::TAG),
        ));

        $this->app->tag([ToolHighlights::class], CoreServiceProvider::HOMEPAGE_SOURCES);

        // سقف پلن رایگان را ماژول درآمدزایی اجرا می‌کند، ولی شمردن کار
        // صاحب داده است (قاعده ۱).
        $this->app->tag([SavedCalculationQuota::class], MonetizationServiceProvider::QUOTA_COUNTERS);

        $this->app->tag([ToolSearch::class], SearchSource::TAG);
        $this->app->tag([ToolSitemapSource::class], SitemapSource::TAG);
        $this->app->tag([ToolLinks::class], LinkTargetSource::TAG);
        $this->app->tag([RecentCalculations::class], WorkspaceWidgetSource::TAG);
        $this->app->tag([CalculationReportSource::class], ReportSource::TAG);
    }

    protected function bootModule(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([SyncToolsCommand::class]);
        }

        // محاسبه سریع صفحه اصلی: فقط جهت‌هایی که ابزارشان باز است.
        View::composer('tools::home.quick-convert', function (ViewContract $view): void {
            $catalog = $this->app->make(ToolCatalog::class);

            /** @var list<string> $slugs */
            $slugs = (array) config('tools.quick_convert.tools', []);

            $view->with([
                'directions' => array_values(array_map(
                    static fn (string $slug): ResolvedTool => $catalog->resolve($slug),
                    array_filter($slugs, static fn (string $slug): bool => $catalog->has($slug) && $catalog->resolve($slug)->usable()),
                )),
                'substances' => (array) config('tools.quick_convert.substances', []),
            ]);
        });
    }
}
