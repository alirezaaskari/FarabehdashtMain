<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Providers;

use App\Contracts\ConsultantDirectory;
use App\Contracts\SearchSource;
use App\Contracts\SitemapSource;
use App\Modules\Admin\Providers\AdminServiceProvider;
use App\Modules\Consulting\Admin\PendingConsultantProfiles;
use App\Modules\Consulting\Listeners\SyncConsultantVisibility;
use App\Modules\Consulting\Search\ConsultantSearch;
use App\Modules\Consulting\Seo\ConsultantSitemapSource;
use App\Modules\Consulting\Services\ConsultantProfileLinks;
use App\Modules\Identity\Events\ProfileApproved;
use App\Modules\Identity\Events\ProfileDeactivated;
use App\Support\Modules\ModuleProvider;
use Illuminate\Support\Facades\Event;

/**
 * مشاوره (بخش ۱۹): صفحه عمومی مشاور و، در گام‌های بعد، فروش خدمت.
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
        $this->app->bind(ConsultantDirectory::class, ConsultantProfileLinks::class);

        $this->app->tag([ConsultantSitemapSource::class], SitemapSource::TAG);
        $this->app->tag([ConsultantSearch::class], SearchSource::TAG);
        $this->app->tag([PendingConsultantProfiles::class], AdminServiceProvider::APPROVAL_SOURCES);
    }

    protected function bootModule(): void
    {
        Event::listen([ProfileApproved::class, ProfileDeactivated::class], SyncConsultantVisibility::class);
    }
}
