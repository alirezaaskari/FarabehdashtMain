<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Services;

use App\Models\User;
use App\Modules\Encyclopedia\Actions\PublishArticle;
use App\Modules\Encyclopedia\Actions\SubmitArticleForReview;
use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\ArticleReference;
use App\Modules\Encyclopedia\Domain\ArticleSection;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use App\Modules\Encyclopedia\Domain\Enums\ArticleType;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * مقاله‌های آغازین دانشنامه — محتوای واقعی سایت زنده، نه نمونه توسعه.
 *
 * هر مقاله یک فایل در `resources/starter` است. نصب **فقط ردیف تازه می‌سازد**:
 * اگر شناسه‌ای از قبل باشد، هرگز دست نمی‌خورد، پس ویرایش مدیر در پنل با
 * استقرار بعدی بازنویسی نمی‌شود و اجرای دوباره بی‌خطر است.
 *
 * مقاله تازه «در انتظار بازبینی» می‌نشیند، نه منتشرشده: قاعده دانشنامه بازبین
 * علمی واقعی می‌خواهد و نصب خودکار در استقرار کسی را بازبین جا نمی‌زند.
 * انتشار یا در پنل است، یا با `publish()` و نام بازبینی که صریحاً داده شود.
 * نویسنده خالی می‌ماند و صفحه آن را «تحریریه فرابهداشت» نشان می‌دهد.
 */
final readonly class StarterArticles
{
    public function __construct(
        private DatabaseManager $db,
        private SubmitArticleForReview $submit,
        private PublishArticle $publish,
        private string $directory,
    ) {}

    /**
     * @return list<array{slug: string, type: ArticleType, title: string, summary: string, sections: list<array{heading: string, body: string, note?: string, tool?: string}>, references: list<array{title: string, publisher?: string, edition?: string, year?: int, url?: string}>}>
     */
    public function all(): array
    {
        $files = glob($this->directory.'/*.php') ?: [];
        sort($files);

        return array_map(static function (string $file): array {
            /** @var array{slug: string, type: ArticleType, title: string, summary: string, sections: list<array{heading: string, body: string, note?: string, tool?: string}>, references: list<array{title: string, publisher?: string, edition?: string, year?: int, url?: string}>} $draft */
            $draft = require $file;

            return $draft;
        }, $files);
    }

    /** @return int تعداد مقاله‌هایی که تازه ساخته شدند */
    public function install(): int
    {
        $existing = Article::query()->pluck('slug')->all();
        $created = 0;

        foreach ($this->all() as $draft) {
            if (in_array($draft['slug'], $existing, true)) {
                continue;
            }

            $this->db->transaction(fn (): Article => $this->submit->handle($this->create($draft)));
            $created++;
        }

        return $created;
    }

    /**
     * مقاله‌های آغازینی که هنوز در انتظار بازبینی‌اند، با این بازبین منتشر می‌شوند.
     *
     * مقاله‌ای که مدیر پیش‌نویس کرده، برگردانده یا بازنشسته کرده دست نمی‌خورد.
     *
     * @return int تعداد مقاله‌های منتشرشده
     */
    public function publish(User $reviewer, ?Carbon $now = null): int
    {
        if (! $reviewer->can('admin.content.review')) {
            throw new RuntimeException('این حساب اجازه بازبینی محتوا ندارد.');
        }

        $now ??= Carbon::now();
        $slugs = array_column($this->all(), 'slug');

        $pending = Article::query()
            ->whereIn('slug', $slugs)
            ->where('status', ArticleStatus::InReview->value)
            ->get();

        foreach ($pending as $article) {
            $article->forceFill(['reviewer_id' => $reviewer->getKey(), 'reviewed_at' => $now])->save();
            $this->publish->handle($article, (int) $reviewer->getKey(), $now);
        }

        return $pending->count();
    }

    /**
     * @param  array{slug: string, type: ArticleType, title: string, summary: string, sections: list<array{heading: string, body: string, note?: string, tool?: string}>, references: list<array{title: string, publisher?: string, edition?: string, year?: int, url?: string}>}  $draft
     */
    private function create(array $draft): Article
    {
        $article = Article::query()->create([
            'uuid' => (string) Str::uuid7(),
            'slug' => $draft['slug'],
            'type' => $draft['type'],
            'status' => ArticleStatus::Draft,
            'title' => $draft['title'],
            'summary' => $draft['summary'],
        ]);

        foreach ($draft['sections'] as $position => $section) {
            ArticleSection::query()->create([
                'article_id' => $article->id,
                'position' => $position + 1,
                'heading' => $section['heading'],
                'body' => trim($section['body']),
                'note' => $section['note'] ?? null,
                'tool_slug' => $section['tool'] ?? null,
            ]);
        }

        foreach ($draft['references'] as $position => $reference) {
            ArticleReference::query()->create([
                'article_id' => $article->id,
                'position' => $position + 1,
                'title' => $reference['title'],
                'publisher' => $reference['publisher'] ?? null,
                'edition' => $reference['edition'] ?? null,
                'year' => $reference['year'] ?? null,
                'url' => $reference['url'] ?? null,
            ]);
        }

        return $article;
    }
}
