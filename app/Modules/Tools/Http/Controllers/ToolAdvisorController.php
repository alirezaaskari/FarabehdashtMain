<?php

declare(strict_types=1);

namespace App\Modules\Tools\Http\Controllers;

use App\Modules\Tools\Domain\Advisor\Answers;
use App\Modules\Tools\Domain\Enums\Hazard;
use App\Modules\Tools\Domain\Enums\WorkStage;
use App\Modules\Tools\Services\ToolAdvisor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * دستیار انتخاب ابزار — سه سؤال، هرکدام یک فرم GET.
 */
final readonly class ToolAdvisorController
{
    public function __construct(private ToolAdvisor $advisor) {}

    public function __invoke(Request $request): View
    {
        $answers = Answers::from($request->query->all());

        return view('tools::advisor', [
            'answers' => $answers,
            'step' => $this->advisor->step($answers),
            'steps' => $this->advisor->steps($answers),
            'hazards' => Hazard::cases(),
            'stages' => WorkStage::cases(),
            'situations' => $this->advisor->situations($answers->hazard),
            'chosen' => $this->advisor->situation($answers),
            'suggestions' => $this->advisor->suggestions($answers),
        ]);
    }
}
