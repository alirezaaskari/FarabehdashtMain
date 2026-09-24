<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Providers;

use App\Contracts\EntitlementGate;
use App\Contracts\QuotaCounter;
use App\Contracts\SalesSwitch;
use App\Contracts\SubscriberDiscount;
use App\Contracts\WorkspaceWidgetSource;
use App\Modules\Monetization\Console\ExpireSubscriptionsCommand;
use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Monetization\Services\EntitlementResolver;
use App\Modules\Monetization\Services\ProDiscount;
use App\Modules\Monetization\Services\QuotaTally;
use App\Modules\Monetization\Services\StreamRegistry;
use App\Modules\Monetization\Services\StreamSalesSwitch;
use App\Modules\Monetization\Workspace\PlanWidget;
use App\Support\Modules\ModuleProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\View\View;

/**
 * ماژول درآمدزایی.
 *
 * دو قرارداد را بازنویسی می‌کند که پیش‌فرضشان در `AppServiceProvider` بسته
 * شده: `EntitlementGate` و `SubscriberDiscount`. برداشتن یک خط از
 * `config/modules.php` پیش‌فرض‌ها را برمی‌گرداند و کل سایت بی‌محدودیت و
 * بی‌تخفیف می‌شود — بدون تغییر کد هیچ صفحه‌ای. همین معیار پذیرش بخش ۱۴ است.
 *
 * شمارنده‌های سقف از سمت مقابل می‌آیند: هر ماژول صاحب داده، شمارنده‌اش را با
 * برچسب `QUOTA_COUNTERS` ثبت می‌کند و این ماژول بدون شناختن آن‌ها جمع می‌زند
 * — همان الگوی `AdminServiceProvider::APPROVAL_SOURCES`.
 */
final class MonetizationServiceProvider extends ModuleProvider
{
    /** برچسب کانتینر برای شمارنده‌های سقف پلن رایگان. */
    public const QUOTA_COUNTERS = 'monetization.quota_counters';

    public function moduleName(): string
    {
        return 'Monetization';
    }

    protected function registerModule(): void
    {
        // برچسب همیشه وجود دارد، حتی وقتی هیچ ماژولی شمارنده ثبت نکرده
        // باشد: `tagged` روی برچسب ناشناخته خطا می‌دهد.
        $this->app->tag([], self::QUOTA_COUNTERS);

        $this->app->singleton(QuotaTally::class, fn (): QuotaTally => new QuotaTally(
            /** @var iterable<QuotaCounter> */
            $this->app->tagged(self::QUOTA_COUNTERS),
        ));

        $this->app->singleton(EntitlementGate::class, EntitlementResolver::class);
        $this->app->singleton(SubscriberDiscount::class, ProDiscount::class);
        $this->app->singleton(SalesSwitch::class, StreamSalesSwitch::class);

        $this->app->tag([PlanWidget::class], WorkspaceWidgetSource::TAG);
    }

    protected function bootModule(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ExpireSubscriptionsCommand::class]);
        }

        // زمان‌بندی کنار خود ماژول است نه در routes/console.php، تا حذف پوشه
        // دستور بی‌صاحب جا نگذارد. `schedule:run` از قبل در cron هاست هست.
        $this->callAfterResolving(Schedule::class, static function (Schedule $schedule): void {
            $schedule->command(ExpireSubscriptionsCommand::class)->daily();
        });

        // پوسته سایت حق ندارد از این ماژول چیزی بپرسد (قاعده ۲)، پس پاسخ را
        // همین ماژول به آن می‌رساند. با برداشتن ماژول، متغیر هم نیست و
        // ردیف «اشتراک» خودش از منو و ستون کناری می‌رود.
        $this->app->make('view')->composer(
            ['components.site.header', 'components.site.sidebar'],
            function (View $view): void {
                $view->with(
                    'proSubscriptionOffered',
                    $this->app->make(StreamRegistry::class)->isEnabled(RevenueStream::ProSubscription),
                );
            },
        );
    }
}
