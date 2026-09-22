<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Http\Controllers;

use App\Modules\Core\Seo\Schema;
use App\Modules\Core\Seo\SeoMeta;
use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Services\CrossLinks;
use App\Modules\Encyclopedia\Services\Freshness;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * صفحه یک محتوا و نسخه چاپی‌اش.
 *
 * هر دو از یک `find` می‌گذرند، پس محتوای منتشرنشده نه صفحه عمومی دارد و نه
 * نسخه چاپی. نسخه چاپی یک نشانی جداست و نه یک پارامتر، تا بتوان مستقیم به آن
 * پیوند داد و مرورگر خودش چاپش کند.
 */
final readonly class ArticleController
{
    public function __construct(
        private Freshness $freshness,
        private CrossLinks $crossLinks,
    ) {}

    public function show(string $slug): View
    {
        $article = $this->find($slug);

        return view('encyclopedia::show', [
            'article' => $article,
            'freshness' => $this->freshness->of($article),
            'daysUntilDue' => $this->freshness->daysUntilDue($article),
            'related' => $this->crossLinks->articlesFor($article),
            'tools' => $this->crossLinks->toolsFor($article),
            'crossLinks' => $this->crossLinks,
            'seo' => $this->seo($article),
        ]);
    }

    public function print(string $slug): View
    {
        $article = $this->find($slug);

        return view('encyclopedia::print', [
            'article' => $article,
            'printedAt' => now(),
        ]);
    }

    private function find(string $slug): Article
    {
        $article = Article::query()
            ->published()
            ->where('slug', $slug)
            ->with(['author', 'reviewer', 'sections', 'references', 'links'])
            ->first();

        return $article ?? throw new NotFoundHttpException('این محتوا پیدا نشد.');
    }

    private function seo(Article $article): SeoMeta
    {
        $url = route('encyclopedia.show', $article->slug);

        $meta = new SeoMeta(
            title: $article->title,
            description: $article->summary,
            canonical: $url,
        );

        // داده ساختاریافته فقط چیزی را می‌گوید که در صفحه هست و هیچ ادعای
        // اعتبار رسمی نمی‌کند — نه گواهی، نه تأییدیه.
        return $meta->withSchema(Schema::article(
            headline: $article->title,
            url: $url,
            publishedAt: $article->published_at ?? $article->created_at,
            updatedAt: $article->reviewed_at ?? $article->updated_at,
            description: $article->summary,
            authors: array_values(array_filter([
                $article->author?->name,
                $article->reviewer?->name,
            ])),
        ));
    }
}
