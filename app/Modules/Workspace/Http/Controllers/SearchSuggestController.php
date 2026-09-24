<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Http\Controllers;

use App\Modules\Workspace\Services\SiteSearch;
use App\Support\Search\SearchQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * پیشنهاد فوری جست‌وجو هنگام تایپ.
 *
 * تکه HTML برمی‌گرداند، نه JSON، تا نمایش نتیجه فقط یک قالب داشته باشد و
 * منطق جست‌وجو همان `SiteSearch` صفحه نتایج بماند.
 */
final readonly class SearchSuggestController
{
    private const PER_GROUP = 3;

    public function __construct(private SiteSearch $search) {}

    public function __invoke(Request $request): Response
    {
        $query = SearchQuery::from((string) $request->query('q', ''));

        return response()
            ->view('workspace::search-suggest', [
                'query' => $query,
                'groups' => $this->search->search($query, self::PER_GROUP),
            ])
            ->header('X-Robots-Tag', 'noindex');
    }
}
