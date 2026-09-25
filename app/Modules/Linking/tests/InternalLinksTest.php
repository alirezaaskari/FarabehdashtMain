<?php

declare(strict_types=1);

namespace App\Modules\Linking\Tests;

use App\Contracts\InternalLinker;
use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Encyclopedia\Actions\PublishArticle;
use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\ArticleReference;
use App\Modules\Encyclopedia\Domain\ArticleSection;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use App\Modules\Encyclopedia\Domain\Enums\ArticleType;
use App\Modules\Linking\Actions\RebuildLinks;
use App\Modules\Linking\Domain\InternalLink;
use App\Modules\Linking\Domain\LinkBlock;
use App\Support\Linking\NoLinks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * پیوند داخلی از بازسازی تا صفحه: مقاله به ماده و ابزار پیوند می‌دهد، و
 * صفحه ماده و ابزار می‌گویند کدام مقاله‌ها به آن‌ها اشاره دارند.
 */
final class InternalLinksTest extends TestCase
{
    use RefreshDatabase;

    private Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('fbh:seed-chemicals')->assertSuccessful();

        $this->article = $this->publish('noise-and-solvents', 'مواجهه همزمان', [
            "کارگر رنگ‌کار هم با بنزن سروکار دارد و هم با صدای کمپرسور.\n\nدوباره بنزن و CAS 71-43-2؛ پیوند دوم نمی‌گیرد.",
            'برای دز صدا از ابزار استفاده کنید. بنزن دیگر پیوند ندارد.',
        ]);
    }

    public function test_an_article_links_to_the_substances_and_tools_it_mentions_once_each(): void
    {
        $this->artisan('fbh:links:rebuild')->assertSuccessful();

        $html = $this->get(route('encyclopedia.show', $this->article->slug))->assertOk()->getContent() ?: '';

        $this->assertSame(1, substr_count($html, '>بنزن</a>'), 'هر مقصد فقط یک بار، در اولین جا.');
        $this->assertStringContainsString('<a href="'.route('chemicals.show', 'benzene').'"', $html);
        $this->assertStringContainsString('>دز صدا</a>', $html);
        $this->assertStringNotContainsString('>71-43-2</a>', $html, 'CAS همان مقصد بنزن است و پیوند دوم نمی‌گیرد.');
    }

    public function test_the_substance_and_tool_pages_list_the_articles_that_mention_them(): void
    {
        $this->app->make(RebuildLinks::class)->handle();

        $this->get(route('chemicals.show', 'benzene'))
            ->assertOk()
            ->assertSee('مقاله‌هایی که به این اشاره دارند')
            ->assertSee($this->article->title);

        $this->get(route('tools.show', 'noise-dose'))->assertOk()->assertSee($this->article->title);

        $this->get(route('chemicals.show', 'toluene'))->assertOk()->assertDontSee('مقاله‌هایی که به این اشاره دارند');
    }

    public function test_publishing_rebuilds_the_links_without_waiting_for_a_deploy(): void
    {
        // setUp فقط منتشر کرده و بازسازی را صدا نزده است.
        $this->assertTrue(InternalLink::query()->where('source_key', 'encyclopedia:noise-and-solvents')->where('target_key', 'chemicals:benzene')->exists());
    }

    public function test_an_article_never_links_to_itself(): void
    {
        $this->publish('compressor', 'کمپرسور', ['کمپرسور منبع اصلی صدای کارگاه است.']);

        $this->app->make(RebuildLinks::class)->handle();

        $this->assertTrue(InternalLink::query()->where('source_key', 'encyclopedia:noise-and-solvents')->where('target_key', 'encyclopedia:compressor')->exists());
        $this->assertFalse(InternalLink::query()->where('source_key', 'encyclopedia:compressor')->where('target_key', 'encyclopedia:compressor')->exists());
    }

    public function test_the_number_of_links_is_capped_by_text_length(): void
    {
        config(['linking.min_per_document' => 1, 'linking.per_thousand_words' => 0]);

        $this->app->make(RebuildLinks::class)->handle();

        $this->assertSame(1, InternalLink::query()->where('source_key', 'encyclopedia:noise-and-solvents')->count());
    }

    public function test_an_admin_block_removes_the_link_on_the_next_rebuild(): void
    {
        $this->app->make(RebuildLinks::class)->handle();
        $this->assertTrue(InternalLink::query()->where('target_key', 'chemicals:benzene')->exists());

        LinkBlock::query()->create(['source_key' => 'encyclopedia:noise-and-solvents', 'target_key' => 'chemicals:benzene']);
        $this->app->make(RebuildLinks::class)->handle();

        $this->assertFalse(InternalLink::query()->where('target_key', 'chemicals:benzene')->exists());
        $this->assertTrue(InternalLink::query()->where('target_key', 'tools:noise-dose')->exists(), 'قاعده فقط همان جفت را می‌بندد.');
    }

    public function test_without_the_linking_module_the_article_renders_plain_text(): void
    {
        $this->app->make(RebuildLinks::class)->handle();
        $this->app->instance(InternalLinker::class, new NoLinks);

        $html = $this->get(route('encyclopedia.show', $this->article->slug))->assertOk()->getContent() ?: '';

        $this->assertStringNotContainsString('>بنزن</a>', $html);
        $this->assertStringContainsString('هم با بنزن سروکار دارد', $html);
    }

    public function test_article_text_is_escaped_even_around_links(): void
    {
        $this->publish('escape-check', 'آزمون فرار', ['<b>بنزن</b> در متن']);
        $this->app->make(RebuildLinks::class)->handle();

        $html = $this->get(route('encyclopedia.show', 'escape-check'))->assertOk()->getContent() ?: '';

        $this->assertStringContainsString('&lt;b&gt;', $html);
        $this->assertStringNotContainsString('<b>', $html);
    }

    public function test_only_content_and_super_admins_open_the_links_page(): void
    {
        $page = '/'.config('admin.path').'/internal-links';

        $this->actingAs($this->adminWith(AdminRole::Content))->get($page)->assertOk()->assertSee('صفحه‌های یتیم');
        $this->actingAs($this->adminWith(AdminRole::Super))->get($page)->assertOk();
        $this->actingAs($this->adminWith(AdminRole::Finance))->get($page)->assertForbidden();
    }

    /** @param  list<string>  $bodies */
    private function publish(string $slug, string $title, array $bodies): Article
    {
        $article = Article::query()->create([
            'uuid' => (string) Str::uuid7(),
            'slug' => $slug,
            'type' => ArticleType::Guide,
            'status' => ArticleStatus::Draft,
            'title' => $title,
            'summary' => 'خلاصه‌ای برای آزمون پیوند داخلی.',
            'author_id' => User::factory()->create()->id,
            'reviewer_id' => User::factory()->create()->id,
            'reviewed_at' => Carbon::now()->subMonth(),
        ]);

        foreach ($bodies as $index => $body) {
            ArticleSection::query()->create([
                'article_id' => $article->id,
                'position' => $index + 1,
                'heading' => 'بخش '.($index + 1),
                'body' => $body,
            ]);
        }

        ArticleReference::query()->create([
            'article_id' => $article->id,
            'position' => 1,
            'title' => 'ISO 9612',
            'publisher' => 'مرجع بین‌المللی',
            'year' => 2009,
        ]);

        return $this->app->make(PublishArticle::class)->handle($article->refresh());
    }

    private function adminWith(AdminRole $role): User
    {
        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, $role);

        return $user->fresh() ?? $user;
    }
}
