<?php

declare(strict_types=1);

namespace App\Modules\Tools\Http\Controllers;

use App\Modules\Tools\Services\ToolCatalog;
use Illuminate\Contracts\View\View;

/**
 * مرکز ابزارها.
 */
final readonly class ToolIndexController
{
    public function __construct(private ToolCatalog $catalog) {}

    public function __invoke(): View
    {
        return view('tools::index', [
            'groups' => $this->catalog->grouped(),
        ]);
    }
}
