<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Seo;

use App\Contracts\SitemapSource;
use App\Modules\Encyclopedia\Domain\Article;
use App\Support\Seo\SitemapUrl;
use Illuminate\Support\Facades\Route;

/**
 * نشانی‌های دانشنامه برای نقشه سایت.
 *
 * فقط محتوای منتشرشده؛ پیش‌نویس و بازنشسته نشانی عمومی ندارند. تاریخ آخرین
 * تغییر، تاریخ **بازبینی** است و نه updated_at: اصلاح یک غلط تایپی، محتوا را
 * برای موتور جست‌وجو تازه نمی‌کند.
 */
final readonly class ArticleSitemapSource implements SitemapSource
{
    public function section(): string
    {
        return 'encyclopedia';
    }

    /** @return iterable<SitemapUrl> */
    public function sitemapUrls(): iterable
    {
        if (! Route::has('encyclopedia.show')) {
            return;
        }

        yield new SitemapUrl(route('encyclopedia.index'));

        foreach (Article::query()->published()->orderBy('id')->cursor() as $article) {
            yield new SitemapUrl(
                loc: route('encyclopedia.show', $article->slug),
                lastmod: $article->reviewed_at ?? $article->published_at,
            );
        }
    }
}
