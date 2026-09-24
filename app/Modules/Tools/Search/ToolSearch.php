<?php

declare(strict_types=1);

namespace App\Modules\Tools\Search;

use App\Contracts\SearchSource;
use App\Modules\Tools\Domain\ResolvedTool;
use App\Modules\Tools\Services\ToolCatalog;
use App\Support\PersianText;
use App\Support\Search\SearchGroup;
use App\Support\Search\SearchHit;
use App\Support\Search\SearchQuery;
use Illuminate\Support\Facades\Route;

/**
 * ابزارها در جست‌وجوی داخلی.
 *
 * فهرست ابزارها در پیکربندی است، نه دیتابیس؛ پس تطبیق در حافظه و با همان
 * یکسان‌سازی فارسی انجام می‌شود. فقط ابزارهای قابل استفاده — ابزاری که مدیر
 * خاموشش کرده پیدا نمی‌شود.
 */
final readonly class ToolSearch implements SearchSource
{
    public function __construct(private ToolCatalog $catalog) {}

    public function search(SearchQuery $query, int $limit): ?SearchGroup
    {
        if (! Route::has('tools.show')) {
            return null;
        }

        $words = PersianText::words($query->text);

        $matches = array_values(array_filter(
            $this->catalog->usable(),
            static function (ResolvedTool $tool) use ($words): bool {
                $haystack = PersianText::normalise($tool->definition->title.' '.$tool->definition->summary.' '.$tool->slug());

                foreach ($words as $word) {
                    if (mb_stripos($haystack, $word) === false) {
                        return false;
                    }
                }

                return true;
            },
        ));

        return new SearchGroup(
            key: 'tools',
            title: 'ابزارها',
            hits: array_map(
                static fn (ResolvedTool $tool): SearchHit => new SearchHit(
                    title: $tool->definition->title,
                    url: route('tools.show', $tool->slug()),
                    summary: $tool->definition->summary,
                ),
                array_slice($matches, 0, $limit),
            ),
            total: count($matches),
            order: 5,
            moreUrl: Route::has('tools.index') ? route('tools.index') : null,
        );
    }

    public function directUrl(SearchQuery $query): ?string
    {
        return null;
    }
}
