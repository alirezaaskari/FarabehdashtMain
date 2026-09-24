<?php

declare(strict_types=1);

namespace App\Modules\Tools\Http\Controllers;

use App\Modules\Tools\Services\RecentTools;
use App\Modules\Tools\Services\ToolCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * مرکز ابزارها.
 */
final readonly class ToolIndexController
{
    public function __construct(
        private ToolCatalog $catalog,
        private RecentTools $recent,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('tools::index', [
            'groups' => $this->catalog->grouped(),
            'recent' => $user === null ? [] : $this->recent->for((int) $user->getKey()),
        ]);
    }
}
