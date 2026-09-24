<?php

declare(strict_types=1);

namespace App\Modules\Core\Seo;

use App\Contracts\SitemapSource;
use App\Support\Seo\SitemapUrl;

/**
 * صفحه اصلی در نقشه سایت. بخش «pages» را با صفحات ثابت ماژول‌های دیگر شریک است.
 */
final readonly class HomeSitemapSource implements SitemapSource
{
    public function section(): string
    {
        return 'pages';
    }

    /** @return iterable<SitemapUrl> */
    public function sitemapUrls(): iterable
    {
        yield new SitemapUrl(route('home'));
    }
}
