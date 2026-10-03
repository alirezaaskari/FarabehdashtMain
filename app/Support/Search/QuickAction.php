<?php

declare(strict_types=1);

namespace App\Support\Search;

/**
 * یک کار در پنل فرمان: عنوانی که دیده می‌شود و مقصدی که باز می‌شود.
 *
 * `keywords` نام‌های دیگری است که کاربر ممکن است بنویسد («ساخت»، «جدید»)؛
 * دیده نمی‌شود، فقط پیدا می‌شود.
 */
final readonly class QuickAction
{
    public function __construct(
        public string $title,
        public string $url,
        public string $keywords = '',
    ) {}

    public function matches(SearchQuery $query): bool
    {
        return $query->matches($this->title.' '.$this->keywords);
    }
}
