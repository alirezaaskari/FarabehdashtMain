<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Tests;

use App\Contracts\ToolDirectory;
use App\Models\User;
use App\Modules\Core\Providers\CoreServiceProvider;
use App\Modules\Core\Seo\SitemapBuilder;
use App\Modules\Encyclopedia\Actions\PublishArticle;
use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\ArticleReference;
use App\Modules\Encyclopedia\Domain\ArticleSection;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use App\Modules\Encyclopedia\Domain\Enums\ArticleType;
use App\Modules\Encyclopedia\Services\CrossLinks;
use App\Support\Tools\ToolSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * مرز ماژول دانشنامه با بقیه — قاعده ۱ و ۲.
 */
final class ModuleBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_module_imports_no_model_from_another_module(): void
    {
        $offenders = [];

        foreach ($this->sourceFiles() as $file) {
            $source = (string) file_get_contents($file);

            preg_match_all('/^use (App\\\\Modules\\\\(?!Encyclopedia)[^;]+);$/m', $source, $matches);

            foreach ($matches[1] as $import) {
                // ارجاع به ServiceProvider ماژول دیگر فقط برای گرفتن نام
                // برچسب کانتینر است و مدل نیست.
                if (str_ends_with($import, 'ServiceProvider')) {
                    continue;
                }

                // Core زیرساخت عرضی است و استثنای قاعده ۱ نیست؛ ولی حتی از
                // آن هم فقط ابزار سئو برداشته می‌شود، نه مدل.
                if (str_starts_with($import, 'App\Modules\Core\Seo\\')) {
                    continue;
                }

                $offenders[] = basename($file).' → '.$import;
            }
        }

        $this->assertSame([], $offenders, 'دانشنامه نباید کلاس ماژول دیگری را import کند.');
    }

    public function test_the_tool_block_disappears_when_the_tools_module_is_gone(): void
    {
        $article = $this->published(toolSlug: 'wbgt-indoor');

        // قرارداد را از کانتینر برمی‌داریم: دقیقاً همان چیزی که با خاموش‌شدن
        // ماژول ابزارها رخ می‌دهد.
        $this->app->forgetInstance(ToolDirectory::class);
        unset($this->app[ToolDirectory::class]);

        $crossLinks = new CrossLinks($this->app, 4);

        $this->assertSame([], $crossLinks->toolsFor($article));
        $this->assertNull($crossLinks->toolFor($article->sections->first()));
    }

    public function test_an_unknown_tool_slug_is_dropped_instead_of_linking_nowhere(): void
    {
        $article = $this->published(toolSlug: 'no-such-tool');

        $this->assertSame([], $this->app->make(CrossLinks::class)->toolsFor($article));

        // صفحه باید همچنان باز شود؛ بلوک ابزار فقط نمی‌آید.
        $this->get(route('encyclopedia.show', $article->slug))
            ->assertOk()
            ->assertDontSee('باز کردن ابزار');
    }

    public function test_only_a_tool_summary_crosses_the_boundary(): void
    {
        // موضوع این تست خود قرارداد است؛ با خاموش‌بودن ماژول ابزارها چیزی
        // برای سنجیدن نیست و تست باید صریح رد شود، نه شکست بخورد.
        if (! $this->app->bound(ToolDirectory::class)) {
            $this->markTestSkipped('ماژول ابزارها فعال نیست.');
        }

        $tool = $this->app->make(ToolDirectory::class)->find('wbgt-indoor');

        $this->assertInstanceOf(ToolSummary::class, $tool);
        $this->assertSame('wbgt-indoor', $tool->slug);
        $this->assertNotSame('', $tool->title);
    }

    public function test_published_articles_reach_the_sitemap_and_drafts_do_not(): void
    {
        $published = $this->published();
        $draft = $this->article();

        $xml = (new SitemapBuilder($this->app->tagged(CoreServiceProvider::SITEMAP_SOURCES)))->toXml();

        $this->assertStringContainsString(route('encyclopedia.show', $published->slug), $xml);
        $this->assertStringNotContainsString(route('encyclopedia.show', $draft->slug), $xml);
    }

    /** @return list<string> */
    private function sourceFiles(): array
    {
        $directory = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path('app/Modules/Encyclopedia')),
        );

        $files = [];

        foreach ($directory as $file) {
            if ($file->isFile() && $file->getExtension() === 'php' && ! str_contains($file->getPathname(), '/tests/')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function published(?string $toolSlug = null): Article
    {
        return $this->app->make(PublishArticle::class)->handle($this->article($toolSlug));
    }

    private function article(?string $toolSlug = null): Article
    {
        $article = Article::query()->create([
            'uuid' => (string) Str::uuid7(),
            'slug' => 'b-'.Str::random(10),
            'type' => ArticleType::Guide,
            'status' => ArticleStatus::Draft,
            'title' => 'متن آزمایشی مرز',
            'summary' => 'خلاصه.',
            'author_id' => User::factory()->create()->id,
            'reviewer_id' => User::factory()->create()->id,
            'reviewed_at' => Carbon::now()->subMonth(),
        ]);

        ArticleSection::query()->create([
            'article_id' => $article->id,
            'position' => 1,
            'heading' => 'بخش یک',
            'body' => 'متن.',
            'tool_slug' => $toolSlug,
        ]);

        ArticleReference::query()->create([
            'article_id' => $article->id,
            'position' => 1,
            'title' => 'ISO 9612',
            'year' => 2009,
        ]);

        return $article->refresh();
    }
}
