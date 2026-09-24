<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Search;

use App\Contracts\SearchSource;
use App\Modules\Encyclopedia\Domain\Article;
use App\Support\Search\SearchGroup;
use App\Support\Search\SearchHit;
use App\Support\Search\SearchQuery;
use Illuminate\Support\Facades\Route;

/**
 * مقاله‌های منتشرشده دانشنامه در جست‌وجوی داخلی.
 */
final readonly class ArticleSearch implements SearchSource
{
    public function search(SearchQuery $query, int $limit): ?SearchGroup
    {
        if (! Route::has('encyclopedia.show')) {
            return null;
        }

        $matches = $query->constrain(Article::query()->published(), ['title', 'summary']);

        return new SearchGroup(
            key: 'encyclopedia',
            title: 'دانشنامه',
            hits: (clone $matches)->orderByDesc('view_count')->limit($limit)->get()
                ->map(static fn (Article $article): SearchHit => new SearchHit(
                    title: $article->title,
                    url: route('encyclopedia.show', $article->slug),
                    summary: $article->summary,
                ))
                ->values()
                ->all(),
            total: $matches->count(),
            order: 10,
        );
    }

    public function directUrl(SearchQuery $query): ?string
    {
        return null;
    }
}
