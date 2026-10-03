<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Http\Controllers;

use App\Models\User;
use App\Modules\Workspace\Services\QuickActions;
use App\Modules\Workspace\Services\SiteSearch;
use App\Support\Search\SearchQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * پیشنهاد فوری جست‌وجو هنگام تایپ، و همان برای پنل فرمان (Ctrl+K).
 *
 * تکه HTML برمی‌گرداند، نه JSON، تا نمایش نتیجه فقط یک قالب داشته باشد و
 * منطق جست‌وجو همان `SiteSearch` صفحه نتایج بماند. پنل فرمان (`palette=1`)
 * کارها را هم بالای نتایج می‌گذارد و بی عبارت هم پاسخ می‌دهد.
 */
final readonly class SearchSuggestController
{
    private const PER_GROUP = 3;

    private const ACTIONS = 6;

    public function __construct(
        private SiteSearch $search,
        private QuickActions $actions,
    ) {}

    public function __invoke(Request $request): Response
    {
        $query = SearchQuery::from((string) $request->query('q', ''));
        $user = $request->user();
        $palette = $request->boolean('palette');

        return response()
            ->view('workspace::search-suggest', [
                'query' => $query,
                'groups' => $this->search->search($query, self::PER_GROUP),
                'actions' => $palette
                    ? $this->actions->for($user instanceof User ? $user : null, $query, self::ACTIONS)
                    : [],
                'palette' => $palette,
            ])
            ->header('X-Robots-Tag', 'noindex');
    }
}
