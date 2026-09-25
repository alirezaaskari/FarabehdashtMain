<?php

declare(strict_types=1);

namespace App\Modules\Expert\Providers;

use App\Contracts\LinkableContentSource;
use App\Contracts\SearchSource;
use App\Contracts\SitemapSource;
use App\Modules\Admin\Providers\AdminServiceProvider;
use App\Modules\Expert\Admin\PendingExpertItems;
use App\Modules\Expert\Linking\QuestionDocuments;
use App\Modules\Expert\Search\QuestionSearch;
use App\Modules\Expert\Seo\QuestionSitemapSource;
use App\Support\Modules\ModuleProvider;

/**
 * پرسش از متخصص (بخش ۱۸-۳).
 *
 * همه اتصال‌های بیرونی از راه برچسب کانتینر است: نقشه سایت، جست‌وجو، موتور
 * پیوند و صف تأیید مدیر. اعلان و پیامک از رویدادهای `UserNotifiableEvent`
 * می‌آیند. پاسخ‌دهنده را توانایی `expert.answer` (پروفایل مشاور تأییدشده)
 * مشخص می‌کند، نه import از ماژول هویت.
 */
final class ExpertServiceProvider extends ModuleProvider
{
    public function moduleName(): string
    {
        return 'Expert';
    }

    protected function registerModule(): void
    {
        $this->app->tag([QuestionSitemapSource::class], SitemapSource::TAG);
        $this->app->tag([QuestionSearch::class], SearchSource::TAG);
        $this->app->tag([QuestionDocuments::class], LinkableContentSource::TAG);
        $this->app->tag([PendingExpertItems::class], AdminServiceProvider::APPROVAL_SOURCES);
    }
}
