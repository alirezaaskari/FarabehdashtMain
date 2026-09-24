<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Seo;

use App\Contracts\SitemapSource;
use App\Modules\Commerce\Domain\Product;
use App\Support\Seo\SitemapUrl;
use Illuminate\Support\Facades\Route;

/**
 * نشانی‌های محصولات فروشگاه برای نقشه سایت — فقط منتشرشده.
 */
final readonly class ProductSitemapSource implements SitemapSource
{
    public function section(): string
    {
        return 'shop';
    }

    /** @return iterable<SitemapUrl> */
    public function sitemapUrls(): iterable
    {
        if (! Route::has('commerce.show')) {
            return;
        }

        yield new SitemapUrl(route('commerce.index'));

        foreach (Product::query()->published()->orderBy('id')->cursor() as $product) {
            yield new SitemapUrl(route('commerce.show', $product->slug), $product->updated_at);
        }
    }
}
