<?php

declare(strict_types=1);

namespace App\Support\Seo;

use DateTimeInterface;
use InvalidArgumentException;

/**
 * یک نشانی در نقشه سایت.
 *
 * `priority` عمداً بازه‌اش بررسی می‌شود: مقدار بیرون از ۰ تا ۱ کل فایل را از
 * نظر موتور جست‌وجو نامعتبر می‌کند و خطایش تا هفته‌ها دیده نمی‌شود.
 */
final readonly class SitemapUrl
{
    /**
     * @param  string  $changefreq  یکی از مقادیر مجاز پروتکل sitemap
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public string $loc,
        public ?DateTimeInterface $lastmod = null,
        public string $changefreq = 'weekly',
        public float $priority = 0.5,
    ) {
        if ($priority < 0.0 || $priority > 1.0) {
            throw new InvalidArgumentException("اولویت نقشه سایت باید بین ۰ و ۱ باشد: {$priority}");
        }

        $allowed = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];

        if (! in_array($changefreq, $allowed, strict: true)) {
            throw new InvalidArgumentException("بسامد تغییر نامعتبر است: {$changefreq}");
        }
    }
}
