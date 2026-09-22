<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Services;

use App\Contracts\ToolDirectory;
use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\ArticleSection;
use App\Support\Tools\ToolSummary;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;

/**
 * پیوند متقابل: محتوای مرتبط و ابزارهای مرتبط.
 *
 * دو منبع دارد و ترتیبشان عمدی است: **پیوند دستی همیشه اول**. سرمقاله بهتر از
 * هر الگوریتمی می‌داند کدام دو متن به هم مربوط‌اند؛ پیوند خودکار فقط جای خالی
 * را پر می‌کند تا صفحه تازه بن‌بست نباشد.
 *
 * پیوند خودکار بر پایه هم‌نوع‌بودن است و نه شباهت متن: شباهت متنی روی متن
 * فارسی بدون نمایه‌سازی درست، ارجاع‌های بی‌ربط می‌دهد — بدتر از نبودِ پیوند.
 */
final readonly class CrossLinks
{
    public function __construct(
        private Container $container,
        private int $max,
    ) {}

    /**
     * محتوای مرتبط: اول پیوندهای دستی، بعد هم‌نوع‌های منتشرشده.
     *
     * @return Collection<int, Article>
     */
    public function articlesFor(Article $article): Collection
    {
        /** @var Collection<int, Article> $manual */
        $manual = $article->links->filter(
            static fn (Article $linked): bool => $linked->status->publiclyVisible(),
        )->values();

        if ($manual->count() >= $this->max) {
            return $manual->take($this->max)->values();
        }

        $exclude = $manual->pluck('id')->push($article->id)->all();

        /** @var Collection<int, Article> $automatic */
        $automatic = Article::query()
            ->published()
            ->ofType($article->type)
            ->whereNotIn('id', $exclude)
            ->orderByDesc('reviewed_at')
            ->limit($this->max - $manual->count())
            ->get();

        return $manual->concat($automatic)->values();
    }

    /**
     * ابزارهای نام‌برده‌شده در بخش‌های این محتوا.
     *
     * اگر ماژول ابزارها خاموش باشد، قرارداد بسته نشده و فهرست خالی برمی‌گردد؛
     * صفحه بدون بخش ابزار نمایش داده می‌شود، نه اینکه بشکند.
     *
     * @return list<ToolSummary>
     */
    public function toolsFor(Article $article): array
    {
        if (! $this->container->bound(ToolDirectory::class)) {
            return [];
        }

        $directory = $this->container->make(ToolDirectory::class);

        $slugs = $article->sections
            ->map(static fn (ArticleSection $section): ?string => $section->tool_slug)
            ->filter()
            ->unique()
            ->values();

        $tools = [];

        foreach ($slugs as $slug) {
            $tool = $directory->find((string) $slug);

            if ($tool !== null) {
                $tools[] = $tool;
            }
        }

        return $tools;
    }

    /** معرفی ابزار یک بخش، یا null اگر بخش ابزار ندارد یا ابزار برداشته شده. */
    public function toolFor(ArticleSection $section): ?ToolSummary
    {
        if ($section->tool_slug === null || ! $this->container->bound(ToolDirectory::class)) {
            return null;
        }

        return $this->container->make(ToolDirectory::class)->find($section->tool_slug);
    }
}
