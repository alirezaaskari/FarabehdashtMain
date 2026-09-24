<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Http\Controllers;

use App\Modules\Workspace\Services\SiteSearch;
use App\Support\Search\SearchQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * جست‌وجوی داخلی.
 *
 * عبارت در نشانی است (`?q=`) تا نتیجه قابل اشتراک و قابل بازگشت با دکمه
 * «عقب» مرورگر باشد. شناسه یکتای بی‌ابهام (شماره CAS) مستقیم به صفحه‌اش
 * می‌رود.
 */
final readonly class SearchController
{
    public function __construct(private SiteSearch $search) {}

    public function __invoke(Request $request): View|RedirectResponse
    {
        $query = SearchQuery::from((string) $request->query('q', ''));

        $direct = $this->search->directUrl($query);

        if ($direct !== null) {
            return redirect()->to($direct);
        }

        $groups = $this->search->search($query);

        return view('workspace::search', [
            'query' => $query,
            'groups' => $groups,
            'total' => array_sum(array_map(static fn ($group): int => $group->total, $groups)),
        ]);
    }
}
