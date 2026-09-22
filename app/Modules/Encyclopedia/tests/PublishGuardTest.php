<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Tests;

use App\Models\User;
use App\Modules\Core\Domain\AuditLog;
use App\Modules\Encyclopedia\Actions\PublishArticle;
use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\ArticleReference;
use App\Modules\Encyclopedia\Domain\ArticleSection;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use App\Modules\Encyclopedia\Domain\Enums\ArticleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * معیار پذیرش بخش ۹، نیمه اول: «محتوای بدون بازبین منتشر نمی‌شود.»
 */
final class PublishGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_article_without_a_reviewer_is_not_published(): void
    {
        $article = $this->article(withReviewer: false);

        try {
            $this->publish($article);
            $this->fail('محتوای بدون بازبین نباید منتشر شود.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('بازبین علمی', $exception->getMessage());
        }

        $this->assertSame(ArticleStatus::Draft, $article->refresh()->status);
    }

    public function test_a_reviewer_without_a_review_date_is_not_enough(): void
    {
        $article = $this->article();
        $article->forceFill(['reviewed_at' => null])->save();

        $this->expectException(RuntimeException::class);

        $this->publish($article);
    }

    public function test_an_article_without_enough_versioned_references_is_not_published(): void
    {
        // «روش اندازه‌گیری» دو منبع نسخه‌دار می‌خواهد؛ این یکی فقط یکی دارد.
        $article = $this->article(type: ArticleType::Method, references: 1);

        $this->expectExceptionMessageMatches('/منبع نسخه‌دار/u');

        $this->publish($article);
    }

    public function test_a_reference_without_an_edition_or_year_does_not_count(): void
    {
        $article = $this->article(references: 0);

        ArticleReference::query()->create([
            'article_id' => $article->id,
            'position' => 1,
            'title' => 'ISO 9612',
            'publisher' => 'ISO',
            // نه ویرایش و نه سال: استناد به دو سند متفاوت اشاره می‌کند.
        ]);

        $this->expectException(RuntimeException::class);

        $this->publish($article->refresh());
    }

    public function test_an_article_without_sections_is_not_published(): void
    {
        $article = $this->article(sections: 0);

        $this->expectExceptionMessage('محتوای بدون بخش منتشر نمی‌شود.');

        $this->publish($article);
    }

    public function test_a_reviewed_article_is_published_and_gets_its_review_due_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-01'));

        $article = $this->article(type: ArticleType::Regulation, references: 2);
        $article->forceFill(['reviewed_at' => Carbon::parse('2026-02-01')])->save();

        $published = $this->publish($article->refresh());

        $this->assertSame(ArticleStatus::Published, $published->status);
        $this->assertNotNull($published->published_at);

        // «قوانین و الزامات» شش‌ماهه بازبینی می‌شود، از تاریخ بازبینی و نه
        // از تاریخ انتشار: موعد به بازبینی وابسته است، نه به لحظه دکمه‌زدن.
        $this->assertSame('2026-08-01', $published->review_due_at?->toDateString());

        Carbon::setTestNow();
    }

    public function test_publishing_writes_one_audit_row_naming_the_reviewer(): void
    {
        $article = $this->article();

        $this->publish($article);

        $row = AuditLog::query()->where('action', 'encyclopedia.article_published')->sole();

        $this->assertSame($article->uuid, $row->subject_id);
        $this->assertSame($article->reviewer_id, $row->after['reviewer_id'] ?? null);

        // دفتر رویداد شناسه می‌نویسد، نه داده شخصی.
        $this->assertStringNotContainsString('@', json_encode($row->after, JSON_THROW_ON_ERROR));
    }

    private function publish(Article $article): Article
    {
        return $this->app->make(PublishArticle::class)->handle($article);
    }

    private function article(
        ArticleType $type = ArticleType::Guide,
        bool $withReviewer = true,
        int $references = 1,
        int $sections = 1,
    ): Article {
        $author = User::factory()->create();
        $reviewer = $withReviewer ? User::factory()->create() : null;

        $article = Article::query()->create([
            'uuid' => (string) Str::uuid7(),
            'slug' => 'test-'.Str::random(8),
            'type' => $type,
            'status' => ArticleStatus::Draft,
            'title' => 'متن آزمایشی',
            'summary' => 'خلاصه آزمایشی برای تست انتشار.',
            'author_id' => $author->id,
            'reviewer_id' => $reviewer?->id,
            'reviewed_at' => $reviewer === null ? null : Carbon::now()->subMonth(),
        ]);

        for ($i = 1; $i <= $sections; $i++) {
            ArticleSection::query()->create([
                'article_id' => $article->id,
                'position' => $i,
                'heading' => 'بخش '.$i,
                'body' => 'متن بخش '.$i,
            ]);
        }

        for ($i = 1; $i <= $references; $i++) {
            ArticleReference::query()->create([
                'article_id' => $article->id,
                'position' => $i,
                'title' => 'ISO '.(9600 + $i),
                'publisher' => 'ISO',
                'year' => 2017,
            ]);
        }

        return $article->refresh();
    }
}
