<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Site;

use App\Contracts\ShelfSource;
use App\Modules\Webinars\Domain\Webinar;

/** رویدادها: صفحه فهرست رویدادهای گذشته را هم نشان می‌دهد، پس هر رویداد منتشرشده کافی است. */
final readonly class WebinarShelf implements ShelfSource
{
    public function shelves(): array
    {
        return [
            'webinars.index' => Webinar::query()->published()->toBase(),
        ];
    }
}
