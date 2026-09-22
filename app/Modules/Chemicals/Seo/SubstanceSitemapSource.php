<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Seo;

use App\Contracts\SitemapSource;
use App\Modules\Chemicals\Domain\Substance;
use App\Support\Seo\SitemapUrl;
use Illuminate\Support\Facades\Route;

/**
 * نشانی‌های صفحه ماده برای نقشه سایت.
 *
 * فقط منتشرشده؛ پیش‌نویس و بازنشسته نشانی عمومی ندارند. تاریخ آخرین تغییر،
 * تاریخ بازبینی داده است، نه updated_at.
 */
final readonly class SubstanceSitemapSource implements SitemapSource
{
    /** @return iterable<SitemapUrl> */
    public function sitemapUrls(): iterable
    {
        if (! Route::has('chemicals.show')) {
            return;
        }

        foreach (Substance::query()->published()->orderBy('id')->cursor() as $substance) {
            yield new SitemapUrl(
                loc: route('chemicals.show', $substance->slug),
                lastmod: $substance->reviewed_at,
                changefreq: 'monthly',
                priority: 0.7,
            );
        }
    }
}
