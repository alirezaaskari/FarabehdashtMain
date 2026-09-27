<?php

declare(strict_types=1);

namespace App\Support\Expert;

use Carbon\CarbonInterface;

/** پاسخ منتشرشده یک مشاور، برای فهرست بیرون از ماژول پرسش از متخصص. */
final readonly class AnswerLink
{
    public function __construct(
        public string $questionTitle,
        public string $url,
        public CarbonInterface $publishedAt,
        public bool $accepted,
    ) {}
}
