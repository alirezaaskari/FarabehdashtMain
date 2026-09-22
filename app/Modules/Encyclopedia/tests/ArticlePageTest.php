<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Tests;

use App\Models\User;
use App\Modules\Encyclopedia\Actions\PublishArticle;
use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\ArticleReference;
use App\Modules\Encyclopedia\Domain\ArticleSection;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use App\Modules\Encyclopedia\Domain\Enums\ArticleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * معیار پذیرش بخش ۹، نیمه دوم:
 * «نسخه چاپی بدون منو و با منابع خروجی می‌دهد.»
 */
final class ArticlePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_print_version_has_no_navigation_and_no_buttons(): void
    {
        $article = $this->published();

        $body = $this->get(route('encyclopedia.print', $article->slug))->assertOk()->getContent() ?: '';

        // پوسته سایت روی برگه چاپی نمی‌آید: نه پیمایش اصلی، نه فوتر.
        $this->assertStringNotContainsString('پیمایش اصلی', $body);
        $this->assertStringNotContainsString('تمامی حقوق محفوظ است', $body);
        $this->assertStringNotContainsString('ورود / ثبت‌نام', $body);
        $this->assertStringNotContainsString('<nav aria-label="مسیر صفحه"', $body);
    }

    public function test_the_print_version_carries_the_versioned_references(): void
    {
        $article = $this->published();

        $this->get(route('encyclopedia.print', $article->slug))
            ->assertOk()
            ->assertSee('منابع')
            ->assertSee('ISO 9612')
            // سال همراه منبع می‌آید؛ بدون آن، استناد به دو سند متفاوت اشاره می‌کند.
            ->assertSee('2009')
            ->assertSee('نویسنده')
            ->assertSee('بازبین علمی');
    }

    public function test_a_citation_keeps_the_digits_of_its_own_script(): void
    {
        $article = $this->published();

        $persian = ArticleReference::query()->create([
            'article_id' => $article->id,
            'position' => 3,
            'title' => 'حدود مجاز مواجهه شغلی ایران',
            'publisher' => 'وزارت بهداشت',
            'year' => 1400,
        ]);

        $latin = $article->references()->where('title', 'ISO 9612')->sole();

        // عنوان لاتین → ارقام لاتین؛ عنوان فارسی → ارقام فارسی.
        $this->assertSame('2009', $latin->version());
        $this->assertSame('۱۴۰۰', $persian->version());

        $this->get(route('encyclopedia.print', $article->slug))
            ->assertOk()
            ->assertSee('(2009)')
            ->assertSee('(۱۴۰۰)', escape: false);
    }

    public function test_the_print_version_is_never_indexed(): void
    {
        $article = $this->published();

        $this->get(route('encyclopedia.print', $article->slug))
            ->assertOk()
            ->assertSee('noindex', escape: false);
    }

    public function test_an_unpublished_article_has_neither_a_page_nor_a_print_version(): void
    {
        $draft = $this->article();

        $this->get(route('encyclopedia.show', $draft->slug))->assertNotFound();
        $this->get(route('encyclopedia.print', $draft->slug))->assertNotFound();
    }

    public function test_the_article_page_shows_the_freshness_indicator_and_both_dates(): void
    {
        $article = $this->published();

        $this->get(route('encyclopedia.show', $article->slug))
            ->assertOk()
            ->assertSee('آخرین بازبینی علمی', escape: false)
            ->assertSee('بازبینی بعدی', escape: false)
            ->assertSee('نسخه چاپی');
    }

    public function test_a_stale_article_warns_the_reader_instead_of_hiding_it(): void
    {
        $article = $this->published();
        $article->forceFill(['review_due_at' => Carbon::now()->subDays(40)])->save();

        $this->get(route('encyclopedia.show', $article->slug))
            ->assertOk()
            ->assertSee('نیازمند بازبینی')
            ->assertSee('روز</strong> گذشته است', escape: false);
    }

    public function test_the_table_of_contents_is_built_from_the_sections(): void
    {
        $article = $this->published();

        $this->get(route('encyclopedia.show', $article->slug))
            ->assertOk()
            ->assertSee('در این مقاله')
            ->assertSee('id="s1"', escape: false)
            ->assertSee('href="#s1"', escape: false);
    }

    public function test_the_index_filters_by_content_type_from_the_url(): void
    {
        $method = $this->published(type: ArticleType::Method, title: 'روش اندازه‌گیری صدا');
        $glossary = $this->published(type: ArticleType::Glossary, title: 'واژه‌نامه مواجهه');

        $this->get(route('encyclopedia.index', ['type' => [ArticleType::Method->value]]))
            ->assertOk()
            ->assertSee($method->title)
            ->assertDontSee($glossary->title);
    }

    public function test_a_draft_never_appears_in_the_index(): void
    {
        $draft = $this->article(title: 'پیش‌نویس پنهان');

        $this->get(route('encyclopedia.index'))->assertOk()->assertDontSee($draft->title);
    }

    private function published(
        ArticleType $type = ArticleType::Guide,
        string $title = 'اندازه‌گیری صدا در محیط کار',
    ): Article {
        $article = $this->article($type, $title);

        return $this->app->make(PublishArticle::class)->handle($article);
    }

    private function article(
        ArticleType $type = ArticleType::Guide,
        string $title = 'اندازه‌گیری صدا در محیط کار',
    ): Article {
        $article = Article::query()->create([
            'uuid' => (string) Str::uuid7(),
            'slug' => Str::slug('a-'.Str::random(10)),
            'type' => $type,
            'status' => ArticleStatus::Draft,
            'title' => $title,
            'summary' => 'روش، تجهیزات و الزامات گزارش‌دهی پایش صدای شغلی.',
            'author_id' => User::factory()->create()->id,
            'reviewer_id' => User::factory()->create()->id,
            'reviewed_at' => Carbon::now()->subMonth(),
        ]);

        ArticleSection::query()->create([
            'article_id' => $article->id,
            'position' => 1,
            'heading' => 'چرا اندازه‌گیری صدا لازم است',
            'body' => "پاراگراف اول.\n\nپاراگراف دوم.",
            'note' => 'تراز لحظه‌ای معیار مواجهه نیست.',
        ]);

        foreach ([['ISO 9612', 2009], ['NIOSH Criteria', 1998]] as $position => [$refTitle, $year]) {
            ArticleReference::query()->create([
                'article_id' => $article->id,
                'position' => $position + 1,
                'title' => $refTitle,
                'publisher' => 'مرجع بین‌المللی',
                'year' => $year,
            ]);
        }

        return $article->refresh();
    }
}
