<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Http\Controllers;

use App\Modules\Chemicals\Services\SubstanceFinder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * فهرست و جست‌وجوی بانک مواد شیمیایی.
 *
 * جست‌وجو از نشانی می‌آید (`?q=`) نه از نشست، تا نتیجه قابل اشتراک باشد.
 */
final readonly class ChemicalIndexController
{
    public function __construct(private SubstanceFinder $finder) {}

    public function __invoke(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));

        return view('chemicals::index', [
            'query' => $query,
            'substances' => $this->finder->search($query, (int) config('chemicals.index.per_page', 12)),
        ]);
    }
}
