<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Providers;

use App\Contracts\SitemapSource;
use App\Contracts\TunableSource;
use App\Modules\Webinars\Actions\SendReminders;
use App\Modules\Webinars\Console\RemindWebinarsCommand;
use App\Modules\Webinars\Seo\WebinarSitemapSource;
use App\Modules\Webinars\Services\Seats;
use App\Modules\Webinars\Settings\WebinarTunables;
use App\Support\Modules\ModuleProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * رویداد و وبینار (بخش ۱۸-۹، DEC-48).
 *
 * جلسه روی سرویس بیرونی برگزار می‌شود و سایت فقط پیوند را، آن هم به
 * ثبت‌نام‌شده و از یک ساعت پیش از شروع، می‌دهد. یادآور از مسیر اعلان
 * میزکار (و پیامک، اگر روشن باشد) می‌رود.
 */
final class WebinarsServiceProvider extends ModuleProvider
{
    public function moduleName(): string
    {
        return 'Webinars';
    }

    protected function registerModule(): void
    {
        $this->app->tag([WebinarTunables::class], TunableSource::TAG);

        $this->app->bind(Seats::class, static fn (): Seats => new Seats((int) config('webinars.hold_minutes', 20)));

        $this->app->bind(SendReminders::class, static fn ($app): SendReminders => new SendReminders(
            $app->make(Dispatcher::class),
            (int) config('webinars.remind_before_minutes', 120),
        ));

        $this->app->tag([WebinarSitemapSource::class], SitemapSource::TAG);
    }

    protected function bootModule(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([RemindWebinarsCommand::class]);
        }

        $this->callAfterResolving(Schedule::class, static function (Schedule $schedule): void {
            $schedule->command(RemindWebinarsCommand::class)->everyFiveMinutes();
        });
    }
}
