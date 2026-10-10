<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Site;

use App\Contracts\ShelfSource;
use App\Modules\Commerce\Domain\Product;

/** فروشگاه: فایل‌های منتشرشده. */
final readonly class ProductShelf implements ShelfSource
{
    public function shelves(): array
    {
        return [
            'commerce.index' => Product::query()->published()->toBase(),
        ];
    }
}
