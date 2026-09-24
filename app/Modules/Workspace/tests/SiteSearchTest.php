<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Tests;

use App\Models\User;
use App\Modules\Chemicals\Domain\Enums\SubstanceStatus;
use App\Modules\Chemicals\Domain\Substance;
use App\Modules\Chemicals\Domain\SubstanceSynonym;
use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Product;
use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use App\Modules\Encyclopedia\Domain\Enums\ArticleType;
use App\Support\Search\SearchQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * جست‌وجوی داخلی (DEC-25): MySQL و یکسان‌سازی فارسی، بدون موتور بیرونی.
 */
final class SiteSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_results_are_grouped_by_module(): void
    {
        $this->article('ارزیابی مواجهه با صدا', 'روش اندازه‌گیری تراز فشار صوت');
        $this->product('قالب گزارش صدا');

        $this->get(route('workspace.search', ['q' => 'صدا']))
            ->assertOk()
            ->assertSee('دانشنامه')
            ->assertSee('ارزیابی مواجهه با صدا')
            ->assertSee('فروشگاه')
            ->assertSee('قالب گزارش صدا');
    }

    public function test_tools_come_from_the_catalog_not_the_database(): void
    {
        $this->get(route('workspace.search', ['q' => 'WBGT']))
            ->assertOk()
            ->assertSee('ابزارها');
    }

    public function test_arabic_yeh_and_kaf_find_persian_text_and_the_other_way_round(): void
    {
        // داده با «ي» و «ك» عربی، جست‌وجو با «ی» و «ک» فارسی.
        $this->article('كنترل مهندسي', 'خلاصه');
        $this->get(route('workspace.search', ['q' => 'کنترل مهندسی']))->assertSee('كنترل مهندسي');

        // و برعکس.
        $this->article('ایمنی کار در ارتفاع', 'خلاصه');
        $this->get(route('workspace.search', ['q' => 'ايمني']))->assertSee('ایمنی کار در ارتفاع');
    }

    public function test_word_order_and_zero_width_non_joiner_do_not_matter(): void
    {
        $this->article("اندازه\u{200C}گیری روشنایی محیط کار", 'خلاصه');

        $this->get(route('workspace.search', ['q' => 'روشنایی اندازه گیری']))
            ->assertSee("اندازه\u{200C}گیری روشنایی محیط کار");
    }

    public function test_a_valid_cas_number_goes_straight_to_the_substance_page(): void
    {
        $this->substance('Toluene', 'تولوئن', '108-88-3');

        $this->get(route('workspace.search', ['q' => '۱۰۸-۸۸-۳']))
            ->assertRedirect(route('chemicals.show', 'toluene'));
    }

    public function test_substances_are_found_by_synonym(): void
    {
        $substance = $this->substance('Toluene', 'تولوئن', '108-88-3');
        SubstanceSynonym::query()->create(['substance_id' => $substance->id, 'name' => 'متیل بنزن']);

        $this->get(route('workspace.search', ['q' => 'متیل']))
            ->assertSee('تولوئن')
            ->assertSee('108-88-3');
    }

    public function test_unpublished_content_is_never_found(): void
    {
        $this->article('پیش‌نویس محرمانه', 'خلاصه', ArticleStatus::Draft);
        $this->product('محصول ردشده', ProductStatus::Rejected);

        $this->get(route('workspace.search', ['q' => 'محرمانه']))->assertSee('نتیجه‌ای پیدا نشد');
        $this->get(route('workspace.search', ['q' => 'ردشده']))->assertSee('نتیجه‌ای پیدا نشد');
    }

    public function test_a_one_letter_query_is_not_run(): void
    {
        $this->assertFalse(SearchQuery::from('ص')->isSearchable());

        $this->get(route('workspace.search', ['q' => 'ص']))->assertSee('دست‌کم ۲ حرف بنویسید');
    }

    public function test_like_wildcards_in_the_query_are_literal(): void
    {
        $this->article('مقاله معمولی', 'خلاصه');

        $this->get(route('workspace.search', ['q' => '%%']))->assertSee('نتیجه‌ای پیدا نشد');
    }

    public function test_search_is_public_and_the_header_links_to_it(): void
    {
        $this->get(route('workspace.search'))->assertOk();

        $this->actingAs(User::factory()->create())
            ->get('/tools')
            ->assertSee(route('workspace.search'));
    }

    public function test_instant_suggestions_are_grouped_and_short(): void
    {
        foreach (range(1, 5) as $i) {
            $this->product('قالب گزارش صدا '.$i);
        }

        $response = $this->get(route('workspace.search.suggest', ['q' => 'صدا']))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex')
            ->assertSee('فروشگاه')
            ->assertSee('همه نتایج برای «صدا»', escape: false)
            ->assertDontSee('<html', escape: false);

        // پنل سربرگ کوتاه است؛ بقیه در صفحه نتایج.
        $this->assertSame(3, substr_count((string) $response->getContent(), 'قالب گزارش صدا'));
    }

    public function test_a_short_query_suggests_nothing(): void
    {
        $this->assertSame('', trim((string) $this->get(route('workspace.search.suggest', ['q' => 'ص']))->getContent()));
    }

    public function test_the_header_search_asks_for_suggestions(): void
    {
        $this->get('/tools')->assertSee('data-search-suggest="'.route('workspace.search.suggest').'"', escape: false);
    }

    public function test_the_404_page_is_never_a_dead_end(): void
    {
        $this->get('/no-such-page-anywhere')
            ->assertNotFound()
            ->assertSee(route('workspace.search'))
            ->assertSee('name="q"', escape: false);
    }

    private function article(string $title, string $summary, ArticleStatus $status = ArticleStatus::Published): Article
    {
        return Article::query()->create([
            'uuid' => (string) Str::uuid7(),
            'slug' => 'a-'.Str::random(8),
            'type' => ArticleType::Article,
            'status' => $status,
            'title' => $title,
            'summary' => $summary,
        ]);
    }

    private function product(string $title, ProductStatus $status = ProductStatus::Published): Product
    {
        return Product::query()->create([
            'uuid' => (string) Str::uuid7(),
            'vendor_user_id' => User::factory()->create()->id,
            'slug' => 'p-'.Str::random(8),
            'title' => $title,
            'price_toman' => 10_000,
            'status' => $status,
        ]);
    }

    private function substance(string $nameEn, string $nameFa, string $cas): Substance
    {
        return Substance::query()->create([
            'uuid' => (string) Str::uuid7(),
            'slug' => strtolower($nameEn),
            'cas_number' => $cas,
            'name_fa' => $nameFa,
            'name_en' => $nameEn,
            'status' => SubstanceStatus::Published,
        ]);
    }
}
