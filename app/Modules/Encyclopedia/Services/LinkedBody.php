<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Services;

use App\Contracts\InternalLinker;
use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Linking\ArticleLinks;
use App\Support\Linking\LinkRef;
use App\Support\Linking\LinkSegment;

/**
 * متن مقاله با پیوندهای داخلی خودکار.
 *
 * موتور پیوند بندها را یک‌جا می‌گیرد (هر مقصد یک بار در کل مقاله، نه در هر
 * بخش) و اینجا دوباره به بخش‌ها تقسیم می‌شوند. اگر ماژول پیوند خاموش باشد،
 * پیش‌فرض `NoLinks` همان متن را بی‌پیوند برمی‌گرداند.
 */
final readonly class LinkedBody
{
    public function __construct(private InternalLinker $linker) {}

    /**
     * @return array<int, list<list<LinkSegment>>> شناسه بخش ← بندها ← تکه‌ها
     */
    public function of(Article $article): array
    {
        $linked = $this->linker->link(ArticleLinks::key($article), ArticleLinks::paragraphs($article));

        $body = [];
        $offset = 0;

        foreach ($article->sections as $section) {
            $count = count($section->paragraphs());
            $body[$section->id] = array_slice($linked, $offset, $count);
            $offset += $count;
        }

        return $body;
    }

    /**
     * ابزارها و موادی که متن به آن‌ها پیوند داده، جز آن‌هایی که ستون کناری
     * خودش دارد.
     *
     * @param  list<string>  $exceptUrls
     * @return list<LinkRef>
     */
    public function mentions(Article $article, array $exceptUrls = []): array
    {
        return array_values(array_filter(
            $this->linker->mentions(ArticleLinks::key($article)),
            static fn (LinkRef $ref): bool => $ref->kind() !== 'encyclopedia' && ! in_array($ref->url, $exceptUrls, true),
        ));
    }
}
