<?php

declare(strict_types=1);

namespace App\Modules\Tools\Http\Controllers;

use App\Modules\Tools\Actions\RunCalculation;
use App\Modules\Tools\Domain\ResolvedTool;
use App\Modules\Tools\Services\ResultPresenter;
use App\Modules\Tools\Services\ToolCatalog;
use App\Modules\Tools\Services\ToolErrorBag;
use App\Modules\Tools\Services\ToolNotFound;
use App\Modules\Tools\Services\ToolPage;
use Farabehdasht\CalcEngine\Exception\InvalidInput;
use Illuminate\Contracts\View\View;
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

        return view('tools::show', [
            'tool' => $tool,
            ...$this->page->for($tool),
            'field' => $request->boolean('field'),
            'calculation' => $calculation,
            'rows' => $calculation === null ? [] : $this->presenter->fromCalculation($calculation),
            'fieldErrors' => $errors,
            'submitted' => $submitted,
        ]);
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
