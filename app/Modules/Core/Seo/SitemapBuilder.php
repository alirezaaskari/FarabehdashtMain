<?php

declare(strict_types=1);

namespace App\Modules\Core\Seo;

use App\Contracts\SitemapSource;
use App\Support\Seo\SitemapUrl;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * ساخت نقشه سایت بخش‌بندی‌شده از منابع ثبت‌شده ماژول‌ها.
 *
 * `/sitemap.xml` فهرست فایل‌هاست (sitemapindex) و هر بخش فایل خودش را دارد.
 * بخشی که از سقف یک فایل بیشتر شود، خودکار چند صفحه می‌شود:
 * `tools`، `tools-2`، `tools-3`.
 *
 * نشانی‌های تکراری حذف می‌شوند، حتی بین دو بخش: نشانی تکراری در نقشه سایت
 * هشدار Search Console دارد. اولین منبع برنده است.
 *
 * خروجی رشته است نه فایل؛ تصمیم درباره کش با لایه بالاتر است.
 */
final readonly class SitemapBuilder
{
    /** پروتکل ۵۰٬۰۰۰ را اجازه می‌دهد؛ حاشیه برای اینکه به سقف مگابایتی هم نرسیم. */
    public const PER_FILE = 45000;

    /**
     * @param  iterable<SitemapSource>  $sources
     * @param  positive-int  $perFile
     */
    public function __construct(private iterable $sources, private int $perFile = self::PER_FILE) {}

    /**
     * نشانی‌ها به تفکیک فایل: کلید، نام فایل بدون پیشوند و پسوند است.
     *
     * @return array<string, list<SitemapUrl>>
     */
    public function files(): array
    {
        $seen = [];
        $sections = [];

        foreach ($this->sources as $source) {
            $section = $source->section();

            if (preg_match('/^[a-z]+$/', $section) !== 1) {
                throw new InvalidArgumentException("نام بخش نقشه سایت باید حروف کوچک لاتین باشد: {$section}");
            }

            foreach ($source->sitemapUrls() as $url) {
                if (isset($seen[$url->loc])) {
                    continue;
                }

                $seen[$url->loc] = true;
                $sections[$section][] = $url;
            }
        }

        ksort($sections);

        $files = [];

        foreach ($sections as $section => $urls) {
            foreach (array_chunk($urls, $this->perFile) as $page => $chunk) {
                $files[$page === 0 ? $section : $section.'-'.($page + 1)] = $chunk;
            }
        }

        return $files;
    }

    /**
     * @param  array<string, list<SitemapUrl>>  $files
     */
    public function indexXml(array $files): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($files as $name => $urls) {
            $lines[] = '  <sitemap>';
            $lines[] = '    <loc>'.htmlspecialchars(url('/sitemap-'.$name.'.xml'), ENT_XML1).'</loc>';

            $lastmod = self::latest($urls);

            if ($lastmod !== null) {
                $lines[] = '    <lastmod>'.$lastmod->format('Y-m-d').'</lastmod>';
            }

            $lines[] = '  </sitemap>';
        }

        $lines[] = '</sitemapindex>';

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  list<SitemapUrl>  $urls
     */
    public function urlsetXml(array $urls): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($urls as $url) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>'.htmlspecialchars($url->loc, ENT_XML1).'</loc>';

            if ($url->lastmod !== null) {
                $lines[] = '    <lastmod>'.$url->lastmod->format('Y-m-d').'</lastmod>';
            }

            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines)."\n";
    }

    /** @param  list<SitemapUrl>  $urls */
    private static function latest(array $urls): ?DateTimeInterface
    {
        $latest = null;

        foreach ($urls as $url) {
            if ($url->lastmod !== null && ($latest === null || $url->lastmod > $latest)) {
                $latest = DateTimeImmutable::createFromInterface($url->lastmod);
            }
        }

        return $latest;
    }
}
