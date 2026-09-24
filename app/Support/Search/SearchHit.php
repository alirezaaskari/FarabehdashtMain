<?php

declare(strict_types=1);

namespace App\Support\Search;

/**
 * یک نتیجه جست‌وجو.
 *
 * `code` شناسه لاتینی مثل شماره CAS است که چپ‌به‌راست نمایش داده می‌شود.
 */
final readonly class SearchHit
{
    public function __construct(
        public string $title,
        public string $url,
        public string $summary = '',
        public ?string $code = null,
    ) {}
}
