<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Home;

use App\Contracts\HomepageSource;
use App\Modules\Encyclopedia\Domain\Article;
use App\Support\Home\HomeItem;
use App\Support\Home\HomeSection;
use Illuminate\Support\Facades\Route;

/**
 * تازه‌ترین مقاله‌های منتشرشده، برای صفحه اصلی.
 *
 * ترتیب بر اساس تاریخ **بازبینی** است و نه انتشار: مقاله‌ای که تازه بازبینی
 * علمی شده، تازه‌تر از مقاله‌ای است که سال پیش منتشر شد و دست نخورده.
 */
final readonly class ArticleHighlights implements HomepageSource
{
    private const LIMIT = 3;

    public function homeSection(): ?HomeSection
    {
        if (! Route::has('encyclopedia.show') || ! Route::has('encyclopedia.index')) {
            return null;
        }

        $articles = Article::query()
            ->published()
            ->orderByDesc('reviewed_at')
            ->orderByDesc('published_at')
            ->limit(self::LIMIT)
            ->get();

        $items = $articles->map(fn (Article $article): HomeItem => new HomeItem(
            title: $article->title,
            url: route('encyclopedia.show', $article->slug),
            kicker: $article->type->label(),
            summary: $article->summary,
            meta: $article->isReviewed() ? 'بازبینی علمی شده' : null,
        ))->all();

        return new HomeSection(
            key: 'encyclopedia',
            title: 'تازه‌های دانشنامه',
            lede: 'هر مقاله بازبین علمی و تاریخ بازبینی دارد.',
            items: $items,
            order: 20,
            moreUrl: route('encyclopedia.index'),
            moreLabel: 'ورود به دانشنامه',
        );
    }
}
