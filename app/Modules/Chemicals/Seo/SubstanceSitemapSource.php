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
    public function section(): string
    {
        return 'chemicals';
    }

    /** @return iterable<SitemapUrl> */
    public function sitemapUrls(): iterable
    {
        if (! Route::has('chemicals.show')) {
            return;
        }

        yield new SitemapUrl(route('chemicals.index'));

        foreach (Substance::query()->published()->orderBy('id')->cursor() as $substance) {
            yield new SitemapUrl(
                loc: route('chemicals.show', $substance->slug),
                lastmod: $substance->reviewed_at,
            );
        }
    }
}
