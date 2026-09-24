<?php

declare(strict_types=1);

namespace App\Support\Seo;

use DateTimeInterface;

/**
 * یک نشانی در نقشه سایت.
 *
 * `changefreq` و `priority` عمداً نیستند: گوگل هر دو را نادیده می‌گیرد و
 * مقدارهای حدسی فقط نقشه را دروغ‌گو می‌کنند. `lastmod` تنها چیزی است که
 * خوانده می‌شود، و فقط اگر راست باشد.
 */
final readonly class SitemapUrl
{
    public function __construct(
        public string $loc,
        public ?DateTimeInterface $lastmod = null,
    ) {}
}
