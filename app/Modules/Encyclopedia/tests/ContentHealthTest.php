<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Tests;

use App\Models\User;
use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\ArticleReference;
use App\Modules\Encyclopedia\Domain\ArticleSection;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use App\Modules\Encyclopedia\Domain\Enums\ArticleType;
use App\Modules\Encyclopedia\Domain\Enums\Freshness as Level;
use App\Modules\Encyclopedia\Services\ContentHealth;
use App\Modules\Encyclopedia\Services\Freshness;
use App\Modules\Encyclopedia\Services\ReviewReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * نمره سلامت محتوا، نشانگر تازگی و یادآور بازبینی.
 */
final class ContentHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_weights_add_up_to_one_hundred(): void
    {
        // اگر جمع وزن‌ها ۱۰۰ نباشد، «نمره ۱۰۰» دیگر یعنی کامل نیست و هر
        // آستانه‌ای در پنل بی‌معنا می‌شود.
        $this->assertSame(100, array_sum((array) config('encyclopedia.health.weights')));
    }

    public function test_a_complete_article_scores_full_marks(): void
    {
        $article = $this->article(sections: 3, references: 2, withTool: true, reviewDueIn: 200);

        $score = $this->health()->for($article);

        $this->assertSame(100, $score->score);
        $this->assertSame([], $score->gaps);
    }

    public function test_an_unreviewed_article_loses_both_review_and_freshness_points(): void
    {
        $article = $this->article(sections: 3, references: 2, withTool: true, reviewer: false);

        $score = $this->health()->for($article);

        // بدون بازبین: هم ۳۰ امتیاز بازبینی می‌رود، هم ۲۵ امتیاز تازگی —
        // چون محتوای بدون بازبینی هرگز «تازه» حساب نمی‌شود.
        $this->assertSame(45, $score->score);
        $this->assertContains('بازبین علمی یا تاریخ بازبینی ثبت نشده است.', $score->gaps);
    }

    public function test_a_reference_without_an_edition_or_year_does_not_earn_the_points(): void
    {
        $article = $this->article(sections: 3, references: 0, withTool: true, reviewDueIn: 200);

        ArticleReference::query()->create([
            'article_id' => $article->id,
            'position' => 1,
            'title' => 'ISO 9612',
            'publisher' => 'ISO',
        ]);

        $score = $this->health()->for($article->refresh());

        $this->assertSame(80, $score->score);
        $this->assertNotSame([], array_filter(
            $score->gaps,
            static fn (string $gap): bool => str_contains($gap, 'منبع نسخه‌دار'),
        ));
    }

    public function test_a_tool_block_counts_as_being_linked(): void
    {
        $withTool = $this->article(sections: 3, references: 2, withTool: true, reviewDueIn: 200);
        $withoutTool = $this->article(sections: 3, references: 2, withTool: false, reviewDueIn: 200);

        $this->assertTrue($this->health()->for($withTool)->criteria['linked']);
        $this->assertFalse($this->health()->for($withoutTool)->criteria['linked']);
    }

    public function test_freshness_warns_before_the_due_date_and_after_it(): void
    {
        $freshness = $this->app->make(Freshness::class);

        $this->assertSame(Level::Fresh, $freshness->of($this->article(reviewDueIn: 200)));
        $this->assertSame(Level::Aging, $freshness->of($this->article(reviewDueIn: 30)));
        $this->assertSame(Level::Stale, $freshness->of($this->article(reviewDueIn: -1)));
    }

    public function test_an_article_with_no_due_date_is_never_treated_as_fresh(): void
    {
        // نبودِ داده هرگز به سود محتوا تفسیر نمی‌شود.
        $this->assertSame(Level::Stale, $this->app->make(Freshness::class)->of($this->article()));
    }

    public function test_the_reminder_lists_only_published_articles_in_order_of_urgency(): void
    {
        $soon = $this->publishedWithDue(10, 'نزدیک');
        $overdue = $this->publishedWithDue(-20, 'گذشته');
        $far = $this->publishedWithDue(300, 'دور');
        $draft = $this->article(reviewDueIn: -5);

        $due = $this->app->make(ReviewReminder::class)->due();

        $this->assertSame(['گذشته', 'نزدیک'], $due->pluck('title')->all());
        $this->assertNotContains($far->title, $due->pluck('title')->all());
        $this->assertNotContains($draft->title, $due->pluck('title')->all());
    }

    private function health(): ContentHealth
    {
        return $this->app->make(ContentHealth::class);
    }

    private function publishedWithDue(int $days, string $title): Article
    {
        $article = $this->article(reviewDueIn: $days, title: $title);
        $article->forceFill(['status' => ArticleStatus::Published])->save();

        return $article->refresh();
    }

    private function article(
        int $sections = 1,
        int $references = 1,
        bool $withTool = false,
        bool $reviewer = true,
        ?int $reviewDueIn = null,
        string $title = 'متن آزمایشی',
    ): Article {
        $article = Article::query()->create([
            'uuid' => (string) Str::uuid7(),
            'slug' => 'h-'.Str::random(10),
            'type' => ArticleType::Guide,
            'status' => ArticleStatus::Draft,
            'title' => $title,
            'summary' => 'خلاصه آزمایشی.',
            'author_id' => User::factory()->create()->id,
            'reviewer_id' => $reviewer ? User::factory()->create()->id : null,
            'reviewed_at' => $reviewer ? Carbon::now()->subMonth() : null,
            'review_due_at' => $reviewDueIn === null ? null : Carbon::now()->addDays($reviewDueIn),
        ]);

        for ($i = 1; $i <= $sections; $i++) {
            ArticleSection::query()->create([
                'article_id' => $article->id,
                'position' => $i,
                'heading' => 'بخش '.$i,
                'body' => 'متن.',
                'tool_slug' => $withTool && $i === 1 ? 'wbgt-indoor' : null,
            ]);
        }

        for ($i = 1; $i <= $references; $i++) {
            ArticleReference::query()->create([
                'article_id' => $article->id,
                'position' => $i,
                'title' => 'ISO '.(9600 + $i),
                'year' => 2017,
            ]);
        }

        return $article->refresh();
    }
}
