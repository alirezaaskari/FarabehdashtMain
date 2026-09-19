<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Seo\SitemapUrl;

/**
 * ماژولی که نشانی‌هایی برای نقشه سایت دارد.
 *
 * هر ماژول محتوایی این قرارداد را پیاده می‌کند و در کانتینر با برچسب
 * `sitemap.sources` ثبت می‌شود؛ ماژول Core نقشه را می‌سازد بدون اینکه بداند
 * چه ماژول‌هایی وجود دارند.
 */
interface SitemapSource
{
    /**
     * @return iterable<SitemapUrl>
     */
    public function sitemapUrls(): iterable;
}
