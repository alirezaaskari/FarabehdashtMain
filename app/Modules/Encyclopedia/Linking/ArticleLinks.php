<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Linking;

use App\Contracts\LinkTargetSource;
use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\ArticleSection;
use App\Support\Linking\LinkTarget;
use Illuminate\Support\Facades\Route;

/**
 * دانشنامه به‌عنوان مقصد پیوند: عنوان هر مقاله و اصطلاح واژه‌نامه.
 *
 * متنی که پیوند در آن گذاشته می‌شود جداست: {@see ArticleDocuments}.
 */
final readonly class ArticleLinks implements LinkTargetSource
{
    public static function key(Article $article): string
    {
        return 'encyclopedia:'.$article->slug;
    }

    /** @return iterable<LinkTarget> */
    public function linkTargets(): iterable
    {
        if (! Route::has('encyclopedia.show')) {
            return;
        }

        foreach (Article::query()->published()->orderBy('id')->cursor() as $article) {
            yield new LinkTarget(self::key($article), $article->title, route('encyclopedia.show', $article->slug), LinkTarget::titlePhrases($article->title));
        }
    }

    /**
     * همه بندهای مقاله به ترتیب نمایش — همان ترتیبی که صفحه چاپ می‌کند.
     *
     * @return list<string>
     */
    public static function paragraphs(Article $article): array
    {
        return array_merge([], ...$article->sections->map(
            static fn (ArticleSection $section): array => $section->paragraphs(),
        )->all());
    }
}
