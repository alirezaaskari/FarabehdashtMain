<?php

declare(strict_types=1);

namespace App\Modules\Core\Seo;

use App\Contracts\SitemapSource;
use App\Support\Seo\SitemapUrl;

/**
 * ساخت نقشه سایت از منابع ثبت‌شده ماژول‌ها.
 *
 * نشانی‌های تکراری حذف می‌شوند: یک محتوا ممکن است هم در فهرست ماژول خودش و هم
 * در صفحه دسته‌بندی بیاید، و نشانی تکراری در نقشه سایت هشدار Search Console دارد.
 *
 * خروجی رشته است نه فایل؛ تصمیم درباره کش و ذخیره با لایه بالاتر است.
 */
final readonly class SitemapBuilder
{
    /** @param  iterable<SitemapSource>  $sources */
    public function __construct(private iterable $sources) {}

    /** @return list<SitemapUrl> */
    public function urls(): array
    {
        $seen = [];
        $urls = [];

        foreach ($this->sources as $source) {
            foreach ($source->sitemapUrls() as $url) {
                if (isset($seen[$url->loc])) {
                    continue;
                }

                $seen[$url->loc] = true;
                $urls[] = $url;
            }
        }

        return $urls;
    }

    public function toXml(): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($this->urls() as $url) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>'.htmlspecialchars($url->loc, ENT_XML1).'</loc>';

            if ($url->lastmod !== null) {
                $lines[] = '    <lastmod>'.$url->lastmod->format('Y-m-d').'</lastmod>';
            }

            $lines[] = '    <changefreq>'.$url->changefreq.'</changefreq>';
            $lines[] = '    <priority>'.number_format($url->priority, 1, '.', '').'</priority>';
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines)."\n";
    }
}
