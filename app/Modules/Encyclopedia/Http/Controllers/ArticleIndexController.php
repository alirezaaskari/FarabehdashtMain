<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Http\Controllers;

use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\Enums\ArticleType;
use App\Modules\Encyclopedia\Domain\Enums\Freshness as Level;
use App\Modules\Encyclopedia\Services\Freshness;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * فهرست دانشنامه با فیلتر نوع محتوا و وضعیت بازبینی.
 *
 * فیلترها از نشانی می‌آیند و نه از نشست: نشانی فیلترشده باید قابل اشتراک و
 * قابل نشانک‌گذاری باشد و همان چیزی را نشان بدهد که فرستنده دیده است.
 */
final readonly class ArticleIndexController
{
    public function __construct(private Freshness $freshness) {}

    public function __invoke(Request $request): View
    {
        $types = $this->selectedTypes($request);
        $review = (string) $request->query('review', '');

        $query = Article::query()
            ->published()
            ->with(['reviewer'])
            ->orderByDesc('reviewed_at');

        if ($types !== []) {
            $query->whereIn('type', array_map(static fn (ArticleType $t): string => $t->value, $types));
        }

        // فیلتر وضعیت بازبینی روی ستون موعد اعمال می‌شود و نه روی مجموعه
        // بارگذاری‌شده: صفحه‌بندی باید روی همان مجموعه فیلترشده کار کند.
        if ($review === 'fresh') {
            $query->whereNotNull('review_due_at')->where('review_due_at', '>', now());
        } elseif ($review === 'due') {
            $query->where(fn ($q) => $q->whereNull('review_due_at')->orWhere('review_due_at', '<=', now()));
        }

        $articles = $query
            ->paginate((int) config('encyclopedia.index.per_page', 9))
            ->withQueryString();

        return view('encyclopedia::index', [
            'articles' => $articles,
            'types' => ArticleType::cases(),
            'selectedTypes' => array_map(static fn (ArticleType $t): string => $t->value, $types),
            'review' => $review,
            'levels' => $articles->getCollection()->mapWithKeys(
                fn (Article $article): array => [$article->id => $this->freshness->of($article)],
            )->all(),
            'staleLevel' => Level::Stale,
        ]);
    }

    /** @return list<ArticleType> */
    private function selectedTypes(Request $request): array
    {
        $raw = $request->query('type', []);
        $values = is_array($raw) ? $raw : [$raw];

        $types = [];

        foreach ($values as $value) {
            $type = ArticleType::tryFrom((string) $value);

            if ($type !== null) {
                $types[] = $type;
            }
        }

        return $types;
    }
}
