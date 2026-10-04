<?php

declare(strict_types=1);

namespace App\Support\Market;

use Carbon\CarbonInterface;

/** یک جمله امتیاز کارفرما درباره مجری؛ نام کارفرما نمی‌آید. */
final readonly class RatingQuote
{
    public function __construct(
        public int $stars,
        public string $comment,
        public CarbonInterface $at,
    ) {}
}
