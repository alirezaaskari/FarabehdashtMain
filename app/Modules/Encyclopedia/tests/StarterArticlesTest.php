<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Tests;

use App\Contracts\ToolDirectory;
use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use App\Modules\Encyclopedia\Domain\Enums\ArticleType;
use App\Modules\Encyclopedia\Services\StarterArticles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * مقاله‌های آغازین دانشنامه روی سایت زنده نصب می‌شوند، پس هم محتوا و هم
 * نصبشان باید بی‌خطر باشد.
 */
final class StarterArticlesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_starter_article_is_well_formed(): void
    {
        $drafts = $this->app->make(StarterArticles::class)->all();
        $slugs = array_column($drafts, 'slug');

        $this->assertCount(22, $drafts);
        $this->assertSame($slugs, array_unique($slugs));

        foreach ($drafts as $draft) {
            $slug = $draft['slug'];

            $this->assertMatchesRegularExpression('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug);
            $this->assertInstanceOf(ArticleType::class, $draft['type'], $slug);
            $this->assertLessThanOrEqual(255, mb_strlen($draft['title']), $slug);
            $this->assertLessThanOrEqual(500, mb_strlen($draft['summary']), $slug);
            $this->assertGreaterThanOrEqual(4, count($draft['sections']), $slug);

            $versioned = array_filter(
                $draft['references'],
                static fn (array $reference): bool => isset($reference['year']) || isset($reference['edition']),
            );
            $this->assertGreaterThanOrEqual($draft['type']->minimumReferences(), count($versioned), $slug);

            foreach ($draft['sections'] as $section) {
                // بدنه متن ساده است: نشانه‌گذاری مارک‌داون یا HTML خام چاپ می‌شد.
                $this->assertDoesNotMatchRegularExpression('/<[a-z\/]|^\s*[-*#]\s|\*\*/mu', $section['body'], $slug);
            }
        }
    }

    public function test_every_inline_tool_exists(): void
    {
        $tools = $this->app->make(ToolDirectory::class);

        foreach ($this->app->make(StarterArticles::class)->all() as $draft) {
            foreach ($draft['sections'] as $section) {
                if (isset($section['tool'])) {
                    $this->assertNotNull($tools->find($section['tool']), $draft['slug'].': '.$section['tool']);
                }
            }
        }
    }

    public function test_install_waits_for_review_and_never_touches_existing_articles(): void
    {
        $this->artisan('fbh:starter-articles')->assertSuccessful();

        $this->assertSame(22, Article::query()->where('status', ArticleStatus::InReview->value)->count());
        $this->assertSame(0, Article::query()->whereNotNull('reviewer_id')->count());

        $edited = Article::query()->where('slug', 'twa-stel-ceiling-idlh')->sole();
        $edited->forceFill(['title' => 'عنوان ویرایش‌شده مدیر'])->save();
        Article::query()->where('slug', 'lockout-tagout')->delete();

        $this->artisan('fbh:starter-articles')->assertSuccessful();

        $this->assertSame(22, Article::query()->count());
        $this->assertSame('عنوان ویرایش‌شده مدیر', $edited->refresh()->title);
    }

    public function test_publish_needs_a_content_reviewer(): void
    {
        $user = User::factory()->create(['mobile' => '09120000001']);

        $this->artisan('fbh:starter-articles', ['--publish' => true])->assertFailed();
        $this->artisan('fbh:starter-articles', ['--publish' => true, '--reviewer' => '09120000001'])->assertFailed();

        $this->assertSame(0, Article::query()->published()->count());

        $this->app->make(GrantAdminRole::class)->handle($user, AdminRole::Content);

        $this->artisan('fbh:starter-articles', ['--publish' => true, '--reviewer' => '09120000001'])->assertSuccessful();

        $this->assertSame(22, Article::query()->published()->where('reviewer_id', $user->id)->count());
    }

    public function test_a_published_starter_article_names_the_editorial_team(): void
    {
        $reviewer = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($reviewer, AdminRole::Content);

        $starter = $this->app->make(StarterArticles::class);
        $starter->install();
        $starter->publish($reviewer->fresh() ?? $reviewer);

        $this->get(route('encyclopedia.show', 'twa-stel-ceiling-idlh'))
            ->assertOk()
            ->assertSee('تحریریه فرابهداشت');
    }
}
