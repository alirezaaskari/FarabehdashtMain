<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Seo\SitemapUrl;

/**
 * ماژولی که نشانی‌هایی برای نقشه سایت دارد.
 *
 * هر ماژول محتوایی این قرارداد را پیاده می‌کند و در کانتینر با برچسب
 * {@see self::TAG} ثبت می‌شود؛ ماژول Core نقشه را می‌سازد بدون اینکه بداند
 * چه ماژول‌هایی وجود دارند.
 *
 * نقشه سایت بخش‌بندی‌شده است: هر بخش فایل خودش را دارد
 * (`/sitemap-encyclopedia.xml`) تا در Search Console معلوم باشد کدام بخش
 * ایندکس نمی‌شود. چند منبع می‌توانند یک بخش را پر کنند.
 */
interface SitemapSource
{
    public const TAG = 'sitemap.sources';

    /** نام بخش: حروف کوچک لاتین، بخشی از نام فایل نقشه. */
    public function section(): string;

    /**
     * @return iterable<SitemapUrl>
     */
    public function sitemapUrls(): iterable;
}
