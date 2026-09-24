<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Product;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use App\Modules\Workspace\Actions\PublishLegalVersion;
use App\Modules\Workspace\Domain\Enums\LegalChange;
use App\Modules\Workspace\Domain\Enums\LegalDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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

    /** کلیدها و نوع‌هایی که هیچ صفحه‌ای نباید در JSON-LD داشته باشد (قاعده محصول و DEC-30). */
    private const FORBIDDEN = ['accreditedBy', 'EducationalOccupationalCredential', 'educationalCredentialAwarded', 'AggregateRating', 'aggregateRating', 'Review', 'review'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('fbh:seed-encyclopedia')->assertSuccessful();
        $this->artisan('fbh:seed-chemicals')->assertSuccessful();

        Course::query()->create([
            'uuid' => (string) Str::uuid7(),
            'instructor_user_id' => User::factory()->create()->id,
            'slug' => 'noise-basics',
            'title' => 'مبانی ارزیابی صدا',
            'description' => 'اندازه‌گیری تراز فشار صوت و محاسبه دوز روزانه در محیط کار.',
            'price_toman' => 150_000,
            'status' => CourseStatus::Published,
        ]);

        Product::query()->create([
            'uuid' => (string) Str::uuid7(),
            'vendor_user_id' => User::factory()->create()->id,
            'slug' => 'noise-report-template',
            'title' => 'قالب گزارش ارزیابی صدا',
            'description' => 'قالب آماده گزارش اندازه‌گیری صدا.',
            'price_toman' => 90_000,
            'status' => ProductStatus::Published,
        ]);

        $this->app->make(PublishLegalVersion::class)
            ->handle(LegalDocument::Terms, 'متن شرایط استفاده.', LegalChange::Minor, null, null, null);
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

    /** @return list<string> */
    private function sitemapUrls(): array
    {
        $urls = [];
        $index = simplexml_load_string($this->get('/sitemap.xml')->assertOk()->getContent() ?: '');
        $this->assertNotFalse($index);

        foreach ($index->sitemap as $file) {
            $urlset = simplexml_load_string($this->get((string) $file->loc)->assertOk()->getContent() ?: '');
            $this->assertNotFalse($urlset);

            foreach ($urlset->url as $url) {
                $urls[] = (string) $url->loc;
            }
        }

        return $urls;
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
