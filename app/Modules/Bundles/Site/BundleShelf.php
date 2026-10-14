<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Site;

use App\Contracts\ShelfSource;
use App\Modules\Bundles\Domain\Bundle;

/** بسته‌های راه‌حل منتشرشده. */
final readonly class BundleShelf implements ShelfSource
{
    public function shelves(): array
    {
        return [
            'bundles.index' => Bundle::query()->published()->toBase(),
        ];
    }
}
