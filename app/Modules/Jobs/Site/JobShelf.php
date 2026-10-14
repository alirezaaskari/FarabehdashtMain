<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Site;

use App\Contracts\ShelfSource;
use App\Modules\Jobs\Domain\JobPosting;

/** فهرست کاریابی: آگهی‌های زنده. */
final readonly class JobShelf implements ShelfSource
{
    public function shelves(): array
    {
        return [
            'jobs.index' => JobPosting::query()->live()->toBase(),
        ];
    }
}
