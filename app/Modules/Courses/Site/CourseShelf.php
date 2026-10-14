<?php

declare(strict_types=1);

namespace App\Modules\Courses\Site;

use App\Contracts\ShelfSource;
use App\Modules\Courses\Domain\Course;

/** فهرست دوره‌های منتشرشده. */
final readonly class CourseShelf implements ShelfSource
{
    public function shelves(): array
    {
        return [
            'courses.index' => Course::query()->published()->toBase(),
        ];
    }
}
