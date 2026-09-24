<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Linking;

use App\Contracts\LinkableContentSource;
use App\Modules\Encyclopedia\Domain\Article;
use App\Support\Linking\LinkableDocument;
use Illuminate\Support\Facades\Route;

/**
 * متن مقاله‌های منتشرشده برای موتور پیوند: بندهای بخش‌ها.
 *
 * عنوان بخش و «نکته کلیدی» پیوند نمی‌گیرند: عنوان جای پیوند نیست و نکته
 * هشدار است، نباید خواننده را از آن دور کرد.
 */
final readonly class ArticleDocuments implements LinkableContentSource
{
    /** @return iterable<LinkableDocument> */
    public function linkableDocuments(): iterable
    {
        if (! Route::has('encyclopedia.show')) {
            return;
        }

        foreach (Article::query()->published()->with('sections')->orderBy('id')->lazy(50) as $article) {
            yield new LinkableDocument(
                ArticleLinks::key($article),
                $article->title,
                route('encyclopedia.show', $article->slug),
                ArticleLinks::paragraphs($article),
            );
        }
    }
}
