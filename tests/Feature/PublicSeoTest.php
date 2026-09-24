<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Commerce\Domain\Product;
use App\Modules\Workspace\Domain\Enums\LegalDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * نگهبان سئوی کل سایت (بخش ۱۷).
 *
 * از نقشه سایت شروع می‌کند، همان‌طور که موتور جست‌وجو می‌کند: هر نشانی که
 * به گوگل معرفی می‌کنیم باید صفحه‌ای واقعی، ایندکس‌پذیر و با Canonical خودش
 * باشد، و داده ساختاریافته‌اش هیچ ادعای اعتبار یا امتیازی نکند.
 */
final class PublicSeoTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPublicSite;

    /** کلیدها و نوع‌هایی که هیچ صفحه‌ای نباید در JSON-LD داشته باشد (قاعده محصول و DEC-30). */
    private const FORBIDDEN = ['accreditedBy', 'EducationalOccupationalCredential', 'educationalCredentialAwarded', 'AggregateRating', 'aggregateRating', 'Review', 'review'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPublicSite();
    }

    public function test_every_sitemap_url_is_a_real_indexable_page_with_its_own_canonical(): void
    {
        $urls = $this->sitemapUrls();

        $this->assertGreaterThan(10, count($urls), 'نقشه سایت باید همه بخش‌ها را داشته باشد.');

        foreach ($urls as $url) {
            $response = $this->get($url);
            $html = $response->getContent() ?: '';

            $response->assertOk();
            $this->assertStringNotContainsString('name="robots" content="noindex', $html, "{$url} در نقشه هست ولی noindex است.");
            $this->assertStringContainsString('<link rel="canonical" href="'.$url.'">', $html, "{$url} باید Canonical خودش باشد.");
            $this->assertSame(1, substr_count($html, '<h1'), "{$url} باید دقیقاً یک H1 داشته باشد.");
            $this->assertMatchesRegularExpression('/<meta name="description" content="[^"]+">/u', $html, "{$url} توضیح ندارد.");
        }
    }

    public function test_titles_and_descriptions_fit_what_google_shows(): void
    {
        // گوگل عنوان را حدود ۶۰ و توضیح را حدود ۱۶۰ نویسه نشان می‌دهد و بقیه را
        // می‌بُرد. سقف‌ها سخت‌اند؛ کوتاه‌بودن توضیح کار محتواست و در گزارش
        // پذیرش (docs/acceptance/v1/seo.md) فهرست شده، نه شکست CI.
        foreach ($this->sitemapUrls() as $url) {
            $html = $this->get($url)->getContent() ?: '';

            preg_match('#<title>(.*?)</title>#su', $html, $title);
            preg_match('#<meta name="description" content="([^"]*)">#u', $html, $description);

            $title = html_entity_decode($title[1] ?? '');
            $description = html_entity_decode($description[1] ?? '');

            $this->assertNotSame('', trim($title), "{$url} عنوان ندارد.");
            $this->assertLessThanOrEqual(60, mb_strlen($title), "{$url}: عنوان «{$title}» بلندتر از ۶۰ نویسه است.");
            $this->assertLessThanOrEqual(160, mb_strlen($description), "{$url}: توضیح بلندتر از ۱۶۰ نویسه است.");
        }
    }

    public function test_a_long_title_drops_the_site_name_instead_of_being_cut(): void
    {
        $html = $this->get(route('encyclopedia.show', 'noise-measurement-at-work'))->getContent() ?: '';

        preg_match('#<title>(.*?)</title>#su', $html, $title);

        $this->assertStringNotContainsString(config('app.name'), $title[1] ?? '');
    }

    public function test_every_section_reaches_the_sitemap(): void
    {
        $index = $this->get('/sitemap.xml')->assertOk()->getContent() ?: '';

        foreach (['pages', 'encyclopedia', 'chemicals', 'tools', 'courses', 'shop'] as $section) {
            $this->assertStringContainsString(url('/sitemap-'.$section.'.xml'), $index, "بخش {$section} در نقشه سایت نیست.");
        }
    }

    public function test_private_and_noindex_pages_never_reach_the_sitemap(): void
    {
        foreach ($this->sitemapUrls() as $url) {
            $path = (string) parse_url($url, PHP_URL_PATH);

            foreach (['/workspace', '/verify', '/search', '/status', '/login', '/pro', '/commerce/cart', '/tools/calculations'] as $private) {
                $this->assertStringStartsNotWith($private, $path, "{$url} نباید در نقشه سایت باشد.");
            }
        }
    }

    public function test_each_content_type_carries_its_schema_type(): void
    {
        $expected = [
            route('home') => 'WebSite',
            route('tools.show', 'noise-dose') => 'WebApplication',
            route('courses.show', 'noise-basics') => 'Course',
            route('commerce.show', 'noise-report-template') => 'Product',
            route('workspace.legal.show', LegalDocument::Terms->value) => 'WebPage',
        ];

        foreach ($expected as $url => $type) {
            $this->assertContains($type, $this->schemaTypes($this->get($url)), "{$url} باید {$type} داشته باشد.");
        }

        $types = [];

        foreach ($this->sitemapUrls() as $url) {
            array_push($types, ...$this->schemaTypes($this->get($url)));
        }

        foreach (['Article', 'DefinedTerm', 'ChemicalSubstance', 'BreadcrumbList'] as $type) {
            $this->assertContains($type, $types, "هیچ صفحه‌ای {$type} ندارد.");
        }
    }

    public function test_no_page_claims_accreditation_or_ratings(): void
    {
        foreach ($this->sitemapUrls() as $url) {
            foreach ($this->jsonLd($this->get($url)) as $block) {
                array_walk_recursive($block, function (mixed $value, string|int $key) use ($url): void {
                    $this->assertNotContains($key, self::FORBIDDEN, "{$url} کلید «{$key}» دارد.");
                    $this->assertNotContains($value, self::FORBIDDEN, "{$url} نوع «{$value}» دارد.");
                });
            }
        }
    }

    public function test_a_vendor_title_cannot_break_out_of_the_structured_data(): void
    {
        Product::query()->where('slug', 'noise-report-template')->update(['title' => '</script><script>alert(1)</script>']);

        $html = $this->get(route('commerce.show', 'noise-report-template'))->assertOk()->getContent() ?: '';

        $this->assertStringNotContainsString('<script>alert(1)', $html);
    }

    /**
     * @param  TestResponse<Response>  $response
     * @return list<array<mixed>>
     */
    private function jsonLd(TestResponse $response): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $response->getContent() ?: '', $matches);

        return array_map(function (string $json): array {
            $decoded = json_decode($json, true);
            $this->assertIsArray($decoded, 'JSON-LD باید JSON معتبر باشد.');

            return $decoded;
        }, $matches[1]);
    }

    /**
     * @param  TestResponse<Response>  $response
     * @return list<string>
     */
    private function schemaTypes(TestResponse $response): array
    {
        $types = [];

        foreach ($this->jsonLd($response) as $block) {
            /** @var list<array<string, mixed>> $nodes */
            $nodes = $block['@graph'] ?? [$block];

            foreach ($nodes as $node) {
                $types[] = (string) ($node['@type'] ?? '');
            }
        }

        return $types;
    }
}
