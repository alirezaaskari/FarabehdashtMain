<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Providers;

use App\Contracts\SearchSource;
use App\Contracts\SitemapSource;
use App\Contracts\UserNotifiableEvent;
use App\Contracts\WalletStatementReader;
use App\Contracts\WorkspaceWidgetSource;
use App\Modules\Identity\Events\UserSignedIn;
use App\Modules\Workspace\Http\Middleware\RequireLegalAcceptance;
use App\Modules\Workspace\Listeners\AcceptLegalOnSignIn;
use App\Modules\Workspace\Listeners\DeliverUserNotices;
use App\Modules\Workspace\Seo\LegalSitemapSource;
use App\Modules\Workspace\Services\Dashboard;
use App\Modules\Workspace\Services\LegalLibrary;
use App\Modules\Workspace\Services\NotificationInbox;
use App\Modules\Workspace\Services\SiteSearch;
use App\Modules\Workspace\Services\StatusBoard;
use App\Modules\Workspace\Services\WorkspaceViews;
use App\Modules\Workspace\Widgets\NotificationsWidget;
use App\Modules\Workspace\Widgets\WalletWidget;
use App\Support\Modules\ModuleProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;

/**
 * میزکار و صفحات عمومی مشترک: وضعیت سرویس، صفحات حقوقی و جست‌وجو.
 *
 * این ماژول هیچ ماژول دیگری را نمی‌شناسد. سه برچسب کانتینر و یک قرارداد
 * رویداد، همه در `app/Contracts`، تنها درهای ورودش هستند:
 *
 * - {@see WorkspaceWidgetSource::TAG} — کارت‌های میزکار
 * - {@see SearchSource::TAG} — گروه‌های جست‌وجو
 * - {@see UserNotifiableEvent} — اعلان‌ها
 *
 * برچسب‌ها روی قراردادند، نه این کلاس، تا ماژول‌های ثبت‌کننده با حذف این پوشه
 * از کار نیفتند (قاعده ۲).
 */
final class WorkspaceServiceProvider extends ModuleProvider
{
    public function moduleName(): string
    {
        return 'Workspace';
    }

    protected function registerModule(): void
    {
        $this->app->tag([], WorkspaceWidgetSource::TAG);
        $this->app->tag([], SearchSource::TAG);

        $this->app->singleton(WorkspaceViews::class);
        $this->app->singleton(NotificationInbox::class);
        $this->app->singleton(LegalLibrary::class);

        $this->app->singleton(Dashboard::class, fn (): Dashboard => new Dashboard(
            $this->app->tagged(WorkspaceWidgetSource::TAG),
        ));

        $this->app->singleton(SiteSearch::class, fn (): SiteSearch => new SiteSearch(
            $this->app->tagged(SearchSource::TAG),
            (int) config('workspace.search.per_group', 5),
        ));

        $this->app->singleton(StatusBoard::class, static fn (): StatusBoard => new StatusBoard(
            (int) config('workspace.status.days', 45),
        ));

        $this->app->bind(NotificationsWidget::class, fn (): NotificationsWidget => new NotificationsWidget(
            $this->app->make(NotificationInbox::class),
            (int) config('workspace.dashboard.latest_notifications', 3),
        ));

        $this->app->tag([NotificationsWidget::class], WorkspaceWidgetSource::TAG);
        $this->app->tag([LegalSitemapSource::class], SitemapSource::TAG);
    }

    protected function bootModule(): void
    {
        // روی قرارداد، نه تک‌تک رویدادها — همان الگوی دفتر رویداد در Core.
        Event::listen(UserNotifiableEvent::class, DeliverUserNotices::class);

        // رویداد ماژول هویت (قاعده ۱ import رویداد را مجاز می‌داند). اگر آن
        // ماژول نباشد، رویداد هرگز منتشر نمی‌شود و این شنونده بی‌اثر است.
        Event::listen(UserSignedIn::class, AcceptLegalOnSignIn::class);

        // کیف پول فقط وقتی کارت دارد که دفتر کل روشن باشد. `bound` در boot
        // پرسیده می‌شود چون ترتیب ثبت ماژول‌ها تضمینی نیست.
        if ($this->app->bound(WalletStatementReader::class)) {
            $this->app->tag([WalletWidget::class], WorkspaceWidgetSource::TAG);
        }

        $this->app->make(Router::class)->pushMiddlewareToGroup('web', RequireLegalAcceptance::class);

        // پوسته سایت حق ندارد از این ماژول چیزی بپرسد (قاعده ۲)؛ شمار
        // خوانده‌نشده‌ها را همین ماژول به آن می‌رساند.
        $this->app->make('view')->composer(
            ['components.site.header', 'components.site.sidebar'],
            function (View $view): void {
                $userId = Auth::id();

                $view->with(
                    'unreadNotifications',
                    is_int($userId) ? $this->app->make(NotificationInbox::class)->unreadCount($userId) : 0,
                );
            },
        );
    }
}
