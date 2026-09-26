<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Http\Controllers;

use App\Contracts\Taxonomy;
use App\Modules\Chemicals\Actions\SaveSubstance;
use App\Modules\Chemicals\Services\SubstanceFinder;
use App\Support\Taxonomy\TermData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * فهرست و جست‌وجوی بانک مواد شیمیایی.
 *
 * جست‌وجو و گروه از نشانی می‌آیند (`?q=`، `?group=`) نه از نشست، تا نتیجه
 * قابل اشتراک باشد. گروه ناشناخته نادیده گرفته می‌شود، نه ۴۰۴.
 */
final readonly class ChemicalIndexController
{
    public function __construct(
        private SubstanceFinder $finder,
        private Taxonomy $taxonomy,
    ) {}

    public function __invoke(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $groups = $this->taxonomy->terms(SaveSubstance::GROUPS);
        $slug = (string) $request->query('group', '');
        $group = collect($groups)->first(static fn (TermData $term): bool => $term->slug === $slug);

        return view('chemicals::index', [
            'query' => $query,
            'groups' => $groups,
            'group' => $group,
            'substances' => $this->finder->search($query, (int) config('chemicals.index.per_page', 12), $group?->slug),
        ]);
    }
}
