<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Seo;

use App\Contracts\SitemapSource;
use App\Modules\Consulting\Services\DirectoryCatalog;
use App\Support\Seo\SitemapUrl;
use Illuminate\Support\Facades\Route;

/**
 * صفحه‌های دایرکتوری که ایندکس‌پذیرند (DEC-59): صفحه اصلی، و هر صفحه خدمت
 * یا خدمت و شهری که دست‌کم دو ارائه‌دهنده منتشرشده دارد.
 */
final readonly class DirectorySitemapSource implements SitemapSource
{
    public function __construct(private DirectoryCatalog $catalog) {}

    public function section(): string
    {
        return 'directory';
    }

    public function sitemapUrls(): iterable
    {
        if (! Route::has('consulting.directory.index')) {
            return;
        }

        yield new SitemapUrl(route('consulting.directory.index'));

        foreach ($this->catalog->coverage() as $service => $cities) {
            if ($this->catalog->isIndexable(array_sum($cities))) {
                yield new SitemapUrl(route('consulting.directory.service', $service));
            }

            foreach ($cities as $city => $count) {
                if ($this->catalog->isIndexable($count)) {
                    yield new SitemapUrl(route('consulting.directory.city', [$service, $city]));
                }
            }
        }
    }
}
