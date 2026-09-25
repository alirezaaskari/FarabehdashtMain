<?php

declare(strict_types=1);

namespace App\Modules\Expert\Search;

use App\Contracts\SearchSource;
use App\Modules\Expert\Domain\ExpertQuestion;
use App\Support\Search\SearchGroup;
use App\Support\Search\SearchHit;
use App\Support\Search\SearchQuery;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * پرسش‌های عمومی در جست‌وجوی سایت.
 */
final readonly class QuestionSearch implements SearchSource
{
    public function search(SearchQuery $query, int $limit): ?SearchGroup
    {
        if (! Route::has('expert.show')) {
            return null;
        }

        $matches = $query->constrain(ExpertQuestion::query()->listed(), ['title', 'body']);

        return new SearchGroup(
            key: 'expert',
            title: 'پرسش از متخصص',
            hits: (clone $matches)->latest('published_at')->limit($limit)->get()
                ->map(static fn (ExpertQuestion $question): SearchHit => new SearchHit(
                    title: $question->title,
                    url: route('expert.show', $question->uuid),
                    summary: Str::limit($question->body, 140),
                ))
                ->values()
                ->all(),
            total: $matches->count(),
            order: 60,
        );
    }

    public function directUrl(SearchQuery $query): ?string
    {
        return null;
    }
}
