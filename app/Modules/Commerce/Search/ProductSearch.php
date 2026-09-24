<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Search;

use App\Contracts\SearchSource;
use App\Modules\Commerce\Domain\Product;
use App\Support\Search\SearchGroup;
use App\Support\Search\SearchHit;
use App\Support\Search\SearchQuery;
use Illuminate\Support\Facades\Route;

/**
 * محصولات منتشرشده فروشگاه در جست‌وجوی داخلی.
 */
final readonly class ProductSearch implements SearchSource
{
    public function search(SearchQuery $query, int $limit): ?SearchGroup
    {
        if (! Route::has('commerce.show')) {
            return null;
        }

        $matches = $query->constrain(Product::query()->published(), ['title', 'description']);

        return new SearchGroup(
            key: 'commerce',
            title: 'فروشگاه',
            hits: (clone $matches)->orderBy('title')->limit($limit)->get()
                ->map(static fn (Product $product): SearchHit => new SearchHit(
                    title: $product->title,
                    url: route('commerce.show', $product->slug),
                    summary: (string) $product->description,
                ))
                ->values()
                ->all(),
            total: $matches->count(),
            order: 40,
            moreUrl: Route::has('commerce.index') ? route('commerce.index', ['q' => $query->raw]) : null,
        );
    }

    public function directUrl(SearchQuery $query): ?string
    {
        return null;
    }
}
