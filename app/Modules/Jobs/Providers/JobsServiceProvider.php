<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Providers;

use App\Contracts\PassportEvidenceSource;
use App\Contracts\QuickActionSource;
use App\Contracts\SearchSource;
use App\Contracts\ShelfSource;
use App\Contracts\SitemapSource;
use App\Modules\Admin\Providers\AdminServiceProvider;
use App\Modules\Identity\Events\ProfileApproved;
use App\Modules\Identity\Events\ProfileDeactivated;
use App\Modules\Jobs\Admin\PendingJobItems;
use App\Modules\Jobs\Console\ExpireBankRequestsCommand;
use App\Modules\Jobs\Console\SendJobAlertDigestsCommand;
use App\Modules\Jobs\Events\PostingPublished;
use App\Modules\Jobs\Listeners\NotifyJobAlerts;
use App\Modules\Jobs\Listeners\SyncCompanyVisibility;
use App\Modules\Jobs\QuickActions\JobQuickActions;
use App\Modules\Jobs\Search\JobSearch;
use App\Modules\Jobs\Seo\JobSitemapSource;
use App\Modules\Jobs\Site\JobShelf;
use App\Support\Modules\ModuleProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;

/**
 * کاریابی (بخش ۲۰): آگهی، درخواست، گذرنامه مهارتی، هشدار شغل و بانک رزومه.
 *
 * کارفرما را توانایی `jobs.post` (نقش کارفرمای تأییدشده) مشخص می‌کند، نه
 * import از ماژول هویت؛ فقط رویدادهای نقش شنیده می‌شوند تا صفحه شرکت با
 * غیرفعال‌شدن نقش پنهان شود.
 */
final class JobsServiceProvider extends ModuleProvider
{
    public function moduleName(): string
    {
        return 'Jobs';
    }

    protected function registerModule(): void
    {
        $this->app->tag([JobSitemapSource::class], SitemapSource::TAG);
        $this->app->tag([JobShelf::class], ShelfSource::TAG);
        $this->app->tag([JobSearch::class], SearchSource::TAG);
        $this->app->tag([PendingJobItems::class], AdminServiceProvider::APPROVAL_SOURCES);

        // برچسب خالی تا وقتی هیچ منبع گذرنامه‌ای ثبت نشده، `tagged` باز هم کار کند.
        $this->app->tag([], PassportEvidenceSource::TAG);
        $this->app->tag([JobQuickActions::class], QuickActionSource::TAG);
    }

    protected function bootModule(): void
    {
        Event::listen([ProfileApproved::class, ProfileDeactivated::class], SyncCompanyVisibility::class);
        Event::listen(PostingPublished::class, NotifyJobAlerts::class);

        if ($this->app->runningInConsole()) {
            $this->commands([SendJobAlertDigestsCommand::class, ExpireBankRequestsCommand::class]);
        }

        // خلاصه روزانه هشدار شغل (DEC-73).
        $this->callAfterResolving(Schedule::class, static function (Schedule $schedule): void {
            $schedule->command(SendJobAlertDigestsCommand::class)
                ->dailyAt((string) config('jobs.alerts.digest_at', '10:00'))
                ->timezone('Asia/Tehran')
                ->withoutOverlapping();

            // مهلت پاسخ بانک رزومه (DEC-72): اعتبار بی‌پاسخ‌ها هر ساعت برمی‌گردد.
            $schedule->command(ExpireBankRequestsCommand::class)->hourly()->withoutOverlapping();
        });
    }
}
