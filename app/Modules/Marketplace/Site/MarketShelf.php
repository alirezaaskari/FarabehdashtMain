<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Site;

use App\Contracts\ShelfSource;
use App\Modules\Marketplace\Domain\MarketProject;

/** بازار پروژه: پروژه‌های باز عمومی. */
final readonly class MarketShelf implements ShelfSource
{
    public function shelves(): array
    {
        return [
            'market.index' => MarketProject::query()->listed()->toBase(),
        ];
    }
}
