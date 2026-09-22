<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Services;

use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\ArticleReference;
use App\Modules\Encyclopedia\Domain\Enums\Freshness as Level;
use App\Support\PersianDigits;

/**
 * نمره سلامت محتوا.
 *
 * هر پنج معیار چیزی است که **خود سیستم** می‌تواند بسنجد. «کیفیت نوشتار» عمداً
 * نیست: نمره‌ای که با حدس ساخته شود، مدیر را به مسیر اشتباه می‌برد و بدتر از
 * نداشتن نمره است.
 *
 * منبعِ بدون ویرایش و سال، منبع حساب نمی‌شود. استناد به «ISO 9612» بدون سال،
 * به دو سند متفاوت اشاره می‌کند و همان چیزی است که این نمره باید بگیرد.
 */
final readonly class ContentHealth
{
    /**
     * @param  array<string, int>  $weights
     */
    public function __construct(
        private Freshness $freshness,
        private array $weights,
        private int $minimumSections,
    ) {}

    public function for(Article $article): HealthScore
    {
        $criteria = [
            'reviewed' => $article->isReviewed(),
            'fresh' => $this->freshness->of($article) !== Level::Stale,
            'references' => $this->versionedReferenceCount($article) >= $article->type->minimumReferences(),
            'depth' => $article->sections->count() >= $this->minimumSections,
            'linked' => $article->links->isNotEmpty() || $this->hasToolBlock($article),
        ];

        $score = 0;

        foreach ($criteria as $key => $met) {
            if ($met) {
                $score += $this->weights[$key] ?? 0;
            }
        }

        return new HealthScore($score, $criteria, $this->gaps($article, $criteria));
    }

    /**
     * @param  array<string, bool>  $criteria
     * @return list<string>
     */
    private function gaps(Article $article, array $criteria): array
    {
        $gaps = [];

        if (! $criteria['reviewed']) {
            $gaps[] = 'بازبین علمی یا تاریخ بازبینی ثبت نشده است.';
        }

        if (! $criteria['fresh']) {
            $gaps[] = 'موعد بازبینی گذشته است.';
        }

        if (! $criteria['references']) {
            $gaps[] = sprintf(
                'دست‌کم %s منبع نسخه‌دار لازم است؛ اکنون %s منبع ثبت شده.',
                PersianDigits::from($article->type->minimumReferences()),
                PersianDigits::from($this->versionedReferenceCount($article)),
            );
        }

        if (! $criteria['depth']) {
            $gaps[] = sprintf(
                'محتوا کمتر از %s بخش دارد.',
                PersianDigits::from($this->minimumSections),
            );
        }

        if (! $criteria['linked']) {
            $gaps[] = 'به هیچ محتوا یا ابزار دیگری پیوند ندارد.';
        }

        return $gaps;
    }

    private function versionedReferenceCount(Article $article): int
    {
        return $article->references
            ->filter(static fn (ArticleReference $reference): bool => $reference->isVersioned())
            ->count();
    }

    private function hasToolBlock(Article $article): bool
    {
        return $article->sections->contains(
            static fn ($section): bool => $section->tool_slug !== null,
        );
    }
}
