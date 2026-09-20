<?php

declare(strict_types=1);

namespace App\Modules\Tools\Http\Controllers;

use App\Modules\Tools\Services\ToolAdvisor;
use Illuminate\Contracts\View\View;

/**
 * دستیار انتخاب ابزار.
 */
final readonly class ToolAdvisorController
{
    public function __construct(private ToolAdvisor $advisor) {}

    public function __invoke(): View
    {
        return view('tools::advisor', [
            'suggestions' => $this->advisor->suggestions(),
        ]);
    }
}
