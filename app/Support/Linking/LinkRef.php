<?php

declare(strict_types=1);

namespace App\Support\Linking;

/**
 * ارجاع به یک صفحه، برای فهرست‌های «مرتبط».
 */
final readonly class LinkRef
{
    public function __construct(
        public string $key,
        public string $title,
        public string $url,
    ) {}

    /** بخش اول کلید: ماژول صاحب صفحه (`tools`، `chemicals`، `encyclopedia`). */
    public function kind(): string
    {
        return explode(':', $this->key, 2)[0];
    }
}
