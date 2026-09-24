<?php

declare(strict_types=1);

namespace App\Support\Search;

/**
 * نتایج یک ماژول در صفحه جست‌وجو.
 *
 * `total` شمار کل تطبیق‌هاست، نه فقط ردیف‌های نشان‌داده‌شده؛ `moreUrl` کاربر
 * را به جست‌وجوی خود آن بخش می‌برد وقتی نتیجه بیش از سهم این صفحه است.
 */
final readonly class SearchGroup
{
    /** @param  list<SearchHit>  $hits */
    public function __construct(
        public string $key,
        public string $title,
        public array $hits,
        public int $total,
        public int $order,
        public ?string $moreUrl = null,
    ) {}
}
