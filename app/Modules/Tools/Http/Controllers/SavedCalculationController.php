<?php

declare(strict_types=1);

namespace App\Modules\Tools\Http\Controllers;

use App\Models\User;
use App\Modules\Tools\Actions\ReplayCalculation;
use App\Modules\Tools\Actions\RunCalculation;
use App\Modules\Tools\Actions\SaveCalculation;
use App\Modules\Tools\Domain\SavedCalculation;
use App\Modules\Tools\Services\ResultPresenter;
use App\Modules\Tools\Services\ToolCatalog;
use App\Modules\Tools\Services\ToolErrorBag;
use App\Modules\Tools\Services\ToolNotFound;
use App\Modules\Tools\Services\ToolPage;
use App\Support\Entitlement\EntitlementDenied;
use App\Support\Entitlement\UpgradeRedirect;
use Farabehdasht\CalcEngine\Exception\InvalidInput;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * ذخیره و نمایش محاسبه.
 *
 * ذخیره، ورودی‌ها را دوباره اجرا می‌کند و به خروجی‌ای که مرورگر فرستاده اعتماد
 * نمی‌کند: عددی که در سابقه کاربر می‌نشیند باید همان باشد که موتور ساخته، نه
 * آنچه در فرم دست‌کاری شده.
 */
final readonly class SavedCalculationController
{
    public function __construct(
        private ToolCatalog $catalog,
        private RunCalculation $run,
        private SaveCalculation $save,
        private ReplayCalculation $replay,
        private ResultPresenter $presenter,
        private ToolPage $page,
    ) {}

    public function index(Request $request): View
    {
        return view('tools::calculations', [
            'calculations' => SavedCalculation::query()
                ->forUser((int) $this->user($request)->getKey())
                ->orderByDesc('created_at')
                ->paginate(20),
        ]);
    }

    public function store(Request $request, string $slug): RedirectResponse|View
    {
        try {
            $tool = $this->catalog->resolve($slug);
        } catch (ToolNotFound $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        }

        /** @var array<string, mixed> $submitted */
        $submitted = $request->except(['_token', 'label']);

        try {
            $calculation = $this->run->handle($tool, $submitted);
        } catch (InvalidInput $exception) {
            return view('tools::show', [
                'tool' => $tool,
                ...$this->page->for($tool),
                'calculation' => null,
                'rows' => [],
                'fieldErrors' => ToolErrorBag::fromInvalidInput($exception),
                'submitted' => $submitted,
            ]);
        }

        $label = $request->string('label')->trim()->value();

        try {
            $saved = $this->save->handle(
                $this->user($request),
                $tool,
                $calculation,
                $label === '' ? null : $label,
            );
        } catch (EntitlementDenied $denied) {
            return UpgradeRedirect::from($denied);
        }

        return redirect()->route('tools.calculations.show', $saved->uuid);
    }

    public function show(Request $request, string $uuid): View
    {
        return view('tools::calculation', $this->present($this->find($request, $uuid)));
    }

    public function print(Request $request, string $uuid): View
    {
        return view('tools::print', $this->present($this->find($request, $uuid)));
    }

    /**
     * @return array<string, mixed>
     */
    private function present(SavedCalculation $saved): array
    {
        return [
            'calculation' => $saved,
            'rows' => $this->presenter->fromStored($saved->outputs),
            'inputs' => $this->presenter->fromStored($saved->inputs),
            'tool' => $this->catalog->has($saved->tool_slug)
                ? $this->catalog->resolve($saved->tool_slug)
                : null,
            // معیار پذیرش بخش ۷، روی همان صفحه: اجرای دوباره با نسخه ذخیره‌شده
            // باید همان عدد را بدهد. اگر ندهد، خواننده گزارش باید بداند.
            'reproducible' => $this->replay->matches($saved),
        ];
    }

    private function find(Request $request, string $uuid): SavedCalculation
    {
        $saved = SavedCalculation::query()->where('uuid', $uuid)->first();

        // محاسبه کاربر دیگر «۴۰۴» است، نه «۴۰۳»: پاسخ متفاوت یعنی اعلام
        // اینکه این شناسه وجود دارد.
        if ($saved === null || $saved->user_id !== $this->user($request)->getKey()) {
            throw new NotFoundHttpException('این محاسبه پیدا نشد.');
        }

        return $saved;
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        assert($user instanceof User);

        return $user;
    }
}
