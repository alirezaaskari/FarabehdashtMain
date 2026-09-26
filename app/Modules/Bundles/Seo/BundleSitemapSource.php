<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Seo;

use App\Contracts\SitemapSource;
use App\Modules\Bundles\Domain\Bundle;
use App\Support\Seo\SitemapUrl;
use Illuminate\Support\Facades\Route;

final readonly class BundleSitemapSource implements SitemapSource
{
    public function section(): string
    {
        return 'bundles';
    }

    public function sitemapUrls(): iterable
    {
        if (! Route::has('bundles.show')) {
            return;
        }

        yield new SitemapUrl(route('bundles.index'));

        foreach (Bundle::query()->published()->orderBy('id')->cursor() as $bundle) {
            yield new SitemapUrl(loc: route('bundles.show', $bundle->slug), lastmod: $bundle->updated_at);
        }
    }
}
