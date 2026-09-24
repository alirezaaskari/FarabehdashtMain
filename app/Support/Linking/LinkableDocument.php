<?php

declare(strict_types=1);

namespace App\Support\Linking;

/**
 * سندی که پیوند خودکار در متنش گذاشته می‌شود.
 *
 * بندها همان بندهایی‌اند که صفحه نمایش می‌دهد؛ عبارتی که از مرز دو بند
 * بگذرد پیوند نمی‌خورد.
 */
final readonly class LinkableDocument
{
    /**
     * @param  list<string>  $paragraphs
     */
    public function __construct(
        public string $key,
        public string $title,
        public string $url,
        public array $paragraphs,
    ) {}
}
