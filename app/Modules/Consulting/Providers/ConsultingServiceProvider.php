<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Providers;

use App\Contracts\ConsultantDirectory;
use App\Contracts\SearchSource;
use App\Contracts\ServiceProviderDirectory;
use App\Contracts\ShelfSource;
use App\Contracts\SitemapSource;
use App\Contracts\TunableSource;
use App\Modules\Admin\Providers\AdminServiceProvider;
use App\Modules\Consulting\Admin\PendingConsultantProfiles;
use App\Modules\Consulting\Admin\PendingConsultingItems;
use App\Modules\Consulting\Console\SweepConsultingOrdersCommand;
use App\Modules\Consulting\Listeners\SyncConsultantVisibility;
use App\Modules\Consulting\Search\ConsultantSearch;
use App\Modules\Consulting\Seo\ConsultantSitemapSource;
use App\Modules\Consulting\Seo\DirectorySitemapSource;
use App\Modules\Consulting\Services\ConsultantProfileLinks;
use App\Modules\Consulting\Services\DirectoryProviders;
use App\Modules\Consulting\Settings\ConsultingTunables;
use App\Modules\Consulting\Site\ConsultingShelf;
use App\Modules\Identity\Events\ProfileApproved;
use App\Modules\Identity\Events\ProfileDeactivated;
use App\Support\Modules\ModuleProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;

/**
 * مشاوره (بخش ۱۹): صفحه عمومی مشاور (۱۹-۲) و فروش خدمت با پول در امانت (۱۹-۳).
 *
 * مشاور را توانایی `consulting.services.manage` (نقش مشاور تأییدشده)
 * مشخص می‌کند، نه import از ماژول هویت؛ فقط رویدادهای نقش شنیده می‌شوند تا
 * صفحه با غیرفعال‌شدن نقش پنهان شود.
 */
final class ConsultingServiceProvider extends ModuleProvider
{
    public function moduleName(): string
    {
        return 'Consulting';
    }

    protected function registerModule(): void
    {
        $this->app->tag([ConsultingTunables::class], TunableSource::TAG);

        $this->app->bind(ConsultantDirectory::class, ConsultantProfileLinks::class);
        $this->app->bind(ServiceProviderDirectory::class, DirectoryProviders::class);

        $this->app->tag([ConsultantSitemapSource::class, DirectorySitemapSource::class], SitemapSource::TAG);
        $this->app->tag([ConsultingShelf::class], ShelfSource::TAG);
        $this->app->tag([ConsultantSearch::class], SearchSource::TAG);
        $this->app->tag([PendingConsultantProfiles::class, PendingConsultingItems::class], AdminServiceProvider::APPROVAL_SOURCES);
    }

    protected function bootModule(): void
    {
        Event::listen([ProfileApproved::class, ProfileDeactivated::class], SyncConsultantVisibility::class);

        if ($this->app->runningInConsole()) {
            $this->commands([SweepConsultingOrdersCommand::class]);
        }

        // مهلت ۴۸ ساعته پاسخ و ۷ روزه آزادسازی (DEC-53، DEC-54).
        $this->callAfterResolving(Schedule::class, static function (Schedule $schedule): void {
            $schedule->command(SweepConsultingOrdersCommand::class)->hourly()->withoutOverlapping();
        });
    }
}
