<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Seo;

use App\Contracts\SitemapSource;
use App\Modules\Webinars\Domain\Webinar;
use App\Support\Seo\SitemapUrl;
use Illuminate\Support\Facades\Route;

final readonly class WebinarSitemapSource implements SitemapSource
{
    public function section(): string
    {
        return 'webinars';
    }

    public function sitemapUrls(): iterable
    {
        if (! Route::has('webinars.show')) {
            return;
        }

        yield new SitemapUrl(route('webinars.index'));

        foreach (Webinar::query()->published()->orderBy('id')->cursor() as $webinar) {
            yield new SitemapUrl(loc: route('webinars.show', $webinar->slug), lastmod: $webinar->updated_at);
        }
    }
}
