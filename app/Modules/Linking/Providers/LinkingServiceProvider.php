<?php

declare(strict_types=1);

namespace App\Modules\Linking\Providers;

use App\Contracts\InternalLinker;
use App\Contracts\LinkableContentChanged;
use App\Contracts\LinkableContentSource;
use App\Contracts\LinkTargetSource;
use App\Modules\Linking\Actions\RebuildLinks;
use App\Modules\Linking\Console\RebuildLinksCommand;
use App\Modules\Linking\Listeners\RebuildLinksOnChange;
use App\Modules\Linking\Services\StoredLinker;
use App\Support\Modules\ModuleProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Event;

/**
 * موتور پیوند داخلی.
 *
 * هیچ ماژولی را نمی‌شناسد. دو برچسب کانتینر درهای ورودش‌اند:
 *
 * - {@see LinkTargetSource::TAG} — صفحه‌هایی که مقصد پیوندند
 * - {@see LinkableContentSource::TAG} — متن‌هایی که پیوند در آن‌ها گذاشته می‌شود
 *
 * و {@see InternalLinker} تنها در خروجی. اگر این ماژول برداشته شود،
 * `NoLinks` پیش‌فرض سر جایش می‌ماند و متن‌ها بی‌پیوند نمایش داده می‌شوند.
 */
final class LinkingServiceProvider extends ModuleProvider
{
    public function moduleName(): string
    {
        return 'Linking';
    }

    protected function registerModule(): void
    {
        $this->app->tag([], LinkTargetSource::TAG);
        $this->app->tag([], LinkableContentSource::TAG);

        $this->app->singleton(InternalLinker::class, StoredLinker::class);

        $this->app->bind(RebuildLinks::class, fn (): RebuildLinks => new RebuildLinks(
            $this->app->tagged(LinkTargetSource::TAG),
            $this->app->tagged(LinkableContentSource::TAG),
            $this->app->make(ConnectionInterface::class),
            (int) config('linking.per_thousand_words', 8),
            (int) config('linking.min_per_document', 3),
            (int) config('linking.min_phrase_length', 3),
        ));
    }

    protected function bootModule(): void
    {
        Event::listen(LinkableContentChanged::class, RebuildLinksOnChange::class);

        if ($this->app->runningInConsole()) {
            $this->commands([RebuildLinksCommand::class]);
        }
    }
}
