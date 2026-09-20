<?php

declare(strict_types=1);

namespace App\Modules\Core\Tests;

use App\Contracts\SitemapSource;
use App\Modules\Core\Providers\CoreServiceProvider;
use App\Modules\Core\Seo\Schema;
use App\Modules\Core\Seo\SeoMeta;
use App\Modules\Core\Seo\SitemapBuilder;
use App\Support\Money;
use App\Support\Seo\SitemapUrl;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * معیار پذیرش بخش ۴: «Sitemap و Schema برای یک صفحه نمونه درست است.»
 */
final class SeoTest extends TestCase
{
    // نقشه سایت از منبع‌های ماژول‌ها ساخته می‌شود و ماژول محتوایی به جدول
    // نیاز دارد؛ بدون پایگاه داده، این تست چیزی را می‌سنجد که در واقعیت نیست.
    use RefreshDatabase;

    public function test_robots_blocks_everything_outside_production(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertStringContainsString('Disallow: /', $response->getContent() ?: '');
        $this->assertStringNotContainsString('Sitemap:', $response->getContent() ?: '');
    }

    public function test_robots_in_production_opens_the_site_but_closes_the_private_paths(): void
    {
        $this->app->detectEnvironment(static fn (): string => 'production');

        $body = $this->get('/robots.txt')->getContent() ?: '';

        $this->assertStringNotContainsString("Disallow: /\n", $body, 'محیط اصلی نباید کل سایت را ببندد.');
        $this->assertStringContainsString('Disallow: /workspace', $body);
        $this->assertStringContainsString('Disallow: /admin', $body);
        $this->assertStringContainsString('Sitemap: '.url('/sitemap.xml'), $body);
    }

    public function test_no_static_file_shadows_the_robots_route(): void
    {
        // وب‌سرور فایل ایستا را پیش از رسیدن به PHP جواب می‌دهد. لاراول یک
        // public/robots.txt پیش‌فرض دارد که همیشه «همه‌چیز آزاد» می‌گوید و
        // مسیر ما را بی‌اثر می‌کند. این تست جلوی برگشتنش را می‌گیرد.
        $this->assertFileDoesNotExist(public_path('robots.txt'));
        $this->assertFileDoesNotExist(public_path('sitemap.xml'));
    }

    public function test_the_sitemap_route_answers_with_valid_xml(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = simplexml_load_string($response->getContent() ?: '');

        $this->assertNotFalse($xml, 'نقشه سایت باید XML معتبر باشد.');
    }

    public function test_modules_contribute_their_urls_and_duplicates_are_dropped(): void
    {
        $this->app->bind('test.sitemap.source', fn (): SitemapSource => new FakeSitemapSource([
            new SitemapUrl('https://example.test/a', new DateTimeImmutable('2026-01-01'), 'daily', 1.0),
            new SitemapUrl('https://example.test/b'),
        ]));
        $this->app->bind('test.sitemap.source.duplicate', fn (): SitemapSource => new FakeSitemapSource([
            new SitemapUrl('https://example.test/a'),
        ]));
        $this->app->tag(
            ['test.sitemap.source', 'test.sitemap.source.duplicate'],
            CoreServiceProvider::SITEMAP_SOURCES,
        );

        $builder = new SitemapBuilder($this->app->tagged(CoreServiceProvider::SITEMAP_SOURCES));
        $xml = $builder->toXml();

        $this->assertCount(2, $builder->urls());
        $this->assertSame(1, substr_count($xml, '<loc>https://example.test/a</loc>'));
        $this->assertStringContainsString('<lastmod>2026-01-01</lastmod>', $xml);
        $this->assertStringContainsString('<priority>1.0</priority>', $xml);
        $this->assertNotFalse(simplexml_load_string($xml));
    }

    public function test_an_out_of_range_priority_is_refused_at_construction(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SitemapUrl('https://example.test/a', priority: 1.5);
    }

    public function test_an_invalid_change_frequency_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SitemapUrl('https://example.test/a', changefreq: 'sometimes');
    }

    public function test_a_noindex_page_never_emits_a_canonical(): void
    {
        // دو پیام متناقض به موتور جست‌وجو؛ یکی از رایج‌ترین ایرادهای سئو.
        $meta = (new SeoMeta('نمونه', canonical: 'https://example.test/a'))->noindexed();

        $this->assertNull($meta->canonical);
        $this->assertTrue($meta->noindex);
    }

    public function test_article_schema_has_the_fields_google_reads(): void
    {
        $schema = Schema::article(
            headline: 'اندازه‌گیری صدا در محیط کار',
            url: 'https://example.test/noise',
            publishedAt: new DateTimeImmutable('2026-01-01 10:00:00', new DateTimeZone('UTC')),
            updatedAt: new DateTimeImmutable('2026-02-01 10:00:00', new DateTimeZone('UTC')),
            description: 'راهنمای عملی اندازه‌گیری تراز صوت.',
            authors: ['علیرضا عسکری'],
        );

        $this->assertSame('https://schema.org', $schema['@context']);
        $this->assertSame('Article', $schema['@type']);
        $this->assertSame('fa-IR', $schema['inLanguage']);
        $this->assertSame('2026-02-01T10:00:00+00:00', $schema['dateModified']);
        $this->assertSame('2026-01-01T10:00:00+00:00', $schema['datePublished']);
        $this->assertSame([['@type' => 'Person', 'name' => 'علیرضا عسکری']], $schema['author']);
        $this->assertNotFalse(json_encode($schema));
    }

    public function test_an_article_without_an_update_date_reuses_the_publish_date(): void
    {
        $schema = Schema::article('عنوان', 'https://example.test/a', new DateTimeImmutable('2026-01-01 10:00:00'));

        $this->assertSame($schema['datePublished'], $schema['dateModified']);
    }

    public function test_product_schema_prices_in_toman(): void
    {
        $schema = Schema::product('قالب گزارش', 'https://example.test/p', Money::toman(120000));

        $this->assertSame(120000, $schema['offers']['price']);
        $this->assertSame('IRT', $schema['offers']['priceCurrency']);
        $this->assertSame('https://schema.org/InStock', $schema['offers']['availability']);
    }

    public function test_course_schema_never_claims_an_official_accreditation(): void
    {
        // قاعده محصول: گواهی داخلی سایت مدرک رسمی معرفی نمی‌شود.
        $schema = Schema::course('دوره صدا', 'https://example.test/c', 'توضیح دوره');

        $this->assertSame('Course', $schema['@type']);
        $this->assertArrayNotHasKey('accreditedBy', $schema);
        $this->assertArrayNotHasKey('educationalCredentialAwarded', $schema);
    }

    public function test_breadcrumbs_are_numbered_from_one(): void
    {
        $schema = Schema::breadcrumbs([
            ['name' => 'خانه', 'url' => 'https://example.test/'],
            ['name' => 'دانشنامه', 'url' => 'https://example.test/encyclopedia'],
        ]);

        $this->assertSame(1, $schema['itemListElement'][0]['position']);
        $this->assertSame(2, $schema['itemListElement'][1]['position']);
    }
}

/** منبع ساختگی نشانی، به‌جای یک ماژول محتوایی واقعی. */
final readonly class FakeSitemapSource implements SitemapSource
{
    /** @param  list<SitemapUrl>  $urls */
    public function __construct(private array $urls) {}

    /** @return iterable<SitemapUrl> */
    public function sitemapUrls(): iterable
    {
        return $this->urls;
    }
}
