<?php

declare(strict_types=1);

namespace App\Modules\Tools\Http\Controllers;

use App\Modules\Tools\Actions\RunCalculation;
use App\Modules\Tools\Domain\ResolvedTool;
use App\Modules\Tools\Services\ResultPresenter;
use App\Modules\Tools\Services\SessionPoints;
use App\Modules\Tools\Services\ToolCatalog;
use App\Modules\Tools\Services\ToolErrorBag;
use App\Modules\Tools\Services\ToolNotFound;
use App\Modules\Tools\Services\ToolPage;
use Farabehdasht\CalcEngine\Exception\InvalidInput;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * صفحه یک ابزار: فرم، و نتیجه همان فرم.
 *
 * محاسبه روی سرور انجام می‌شود و نتیجه روی همان صفحه می‌آید — بدون
 * جاوااسکریپت اجباری. ابزار میدانی باید روی گوشی ضعیف و اینترنت بد هم کار کند.
 */
final readonly class ToolController
{
    public function __construct(
        private ToolCatalog $catalog,
        private RunCalculation $run,
        private ResultPresenter $presenter,
        private ToolPage $page,
        private SessionPoints $points,
    ) {}

    /**
     * فرم خالی، یا پرشده از نشانی (`?molecular_weight=92.14`) وقتی صفحه دیگری
     * مثل صفحه ماده کاربر را با مقدار معلوم به ابزار می‌فرستد. فقط ورودی‌های
     * خود رابطه و فقط مقدار ساده؛ محاسبه تا «محاسبه کن» انجام نمی‌شود.
     */
    public function show(Request $request, string $slug): View
    {
        $tool = $this->find($slug);

        return view('tools::show', [
            'tool' => $tool,
            ...$this->page->for($tool),
            'field' => $request->boolean('field'),
            'points' => $this->points->for($request->session(), $tool->slug()),
            'calculation' => null,
            'rows' => [],
            'fieldErrors' => [],
            'submitted' => array_filter(
                array_intersect_key($request->query->all(), $tool->formula->inputs()),
                is_scalar(...),
            ),
        ]);
    }

    public function calculate(Request $request, string $slug): View
    {
        $tool = $this->find($slug);

        /** @var array<string, mixed> $submitted */
        $submitted = $request->except(['_token', 'field']);

        try {
            $calculation = $this->run->handle($tool, $submitted);
            $errors = [];
        } catch (InvalidInput $exception) {
            $calculation = null;
            $errors = ToolErrorBag::fromInvalidInput($exception);
        }

        $rows = $calculation === null ? [] : $this->presenter->fromCalculation($calculation);
        $points = $rows === []
            ? $this->points->for($request->session(), $tool->slug())
            : $this->points->record($request->session(), $tool->slug(), $submitted, $rows[0]);

        return view('tools::show', [
            'tool' => $tool,
            ...$this->page->for($tool),
            'field' => $request->boolean('field'),
            'points' => $points,
            'calculation' => $calculation,
            'rows' => $rows,
            'fieldErrors' => $errors,
            'submitted' => $submitted,
        ]);
    }

    public function clearPoints(Request $request, string $slug): RedirectResponse
    {
        $tool = $this->find($slug);
        $this->points->clear($request->session(), $tool->slug());

        return redirect()->route('tools.show', $request->boolean('field') ? [$tool->slug(), 'field' => 1] : $tool->slug());
    }

    private function find(string $slug): ResolvedTool
    {
        try {
            $tool = $this->catalog->resolve($slug);
        } catch (ToolNotFound $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        }

        // ابزار خاموش برای کاربر وجود ندارد؛ صفحه‌ای که باز شود ولی کار نکند
        // بدتر از نبودنش است.
        if (! $tool->usable()) {
            throw new NotFoundHttpException(sprintf('ابزار «%s» در دسترس نیست.', $slug));
        }

        return $tool;
    }
}
