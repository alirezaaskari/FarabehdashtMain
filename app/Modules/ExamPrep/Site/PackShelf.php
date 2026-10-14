<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Site;

use App\Contracts\ShelfSource;
use App\Modules\ExamPrep\Domain\ExamPack;

/** بسته‌های آمادگی آزمون منتشرشده. */
final readonly class PackShelf implements ShelfSource
{
    public function shelves(): array
    {
        return [
            'exam_prep.index' => ExamPack::query()->published()->toBase(),
        ];
    }
}
