<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Seo;

use App\Contracts\SitemapSource;
use App\Modules\Marketplace\Services\MarketCatalog;
use App\Support\Seo\SitemapUrl;
use Illuminate\Support\Facades\Route;

/**
 * صفحه‌های بازار که ایندکس‌پذیرند: فهرست، هر پروژه باز عمومی (پروژه خصوصی
 * هرگز، DEC-90)، و صفحه خدمت یا شهری که دست‌کم دو پروژه باز دارد.
 */
final readonly class MarketSitemapSource implements SitemapSource
{
    public function __construct(private MarketCatalog $catalog) {}

    public function section(): string
    {
        return 'market';
    }

    public function sitemapUrls(): iterable
    {
        if (! Route::has('market.index')) {
            return;
        }

        yield new SitemapUrl(route('market.index'));

        foreach ($this->catalog->listed()->latest('published_at')->cursor() as $project) {
            yield new SitemapUrl(route('market.show', $project->id), $project->updated_at);
        }

        foreach ($this->catalog->serviceCounts() as $service => $count) {
            if ($this->catalog->isIndexable($count)) {
                yield new SitemapUrl(route('market.service', $service));
            }
        }

        foreach ($this->catalog->cityCounts() as $city => $count) {
            if ($this->catalog->isIndexable($count)) {
                yield new SitemapUrl(route('market.city', $city));
            }
        }
    }
}
