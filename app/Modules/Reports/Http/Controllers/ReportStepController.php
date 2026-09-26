<?php

declare(strict_types=1);

namespace App\Modules\Reports\Http\Controllers;

use App\Contracts\EntitlementGate;
use App\Modules\Reports\Actions\IssueReport;
use App\Modules\Reports\Actions\SaveReportDraft;
use App\Modules\Reports\Domain\Enums\ReportStep;
use App\Modules\Reports\Domain\Report;
use App\Modules\Reports\Domain\ReportDocument;
use App\Modules\Reports\Services\ReportPdf;
use App\Modules\Reports\Services\ReportSale;
use App\Modules\Reports\Services\ReportSources;
use App\Support\Entitlement\EntitlementDenied;
use App\Support\Entitlement\Feature;
use App\Support\Entitlement\UpgradeRedirect;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;
use LogicException;

/**
 * مرحله دوم تا چهارم گزارش‌ساز، روی یک پیش‌نویس.
 *
 * گزارش صادرشده این صفحه‌ها را نمی‌بیند؛ به صفحه خودش برمی‌گردد.
 */
final readonly class ReportStepController
{
    use FindsReports;

    public function __construct(private ReportSources $sources) {}

    public function details(Request $request, string $uuid): View|RedirectResponse
    {
        return $this->step($request, $uuid, ReportStep::Details, fn (Report $report): array => []);
    }

    public function saveDetails(Request $request, string $uuid, SaveReportDraft $save): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'site' => ['nullable', 'string', 'max:255'],
            'measured_on' => ['nullable', 'string', 'max:64'],
            'author_name' => ['required', 'string', 'max:255'],
        ]);

        return $this->save($request, $uuid, $save, [
            ...$data,
            'include_equipment' => $request->boolean('include_equipment'),
            'include_method' => $request->boolean('include_method'),
        ], 'reports.findings');
    }

    public function findings(Request $request, string $uuid): View|RedirectResponse
    {
        return $this->step($request, $uuid, ReportStep::Findings, fn (Report $report): array => []);
    }

    public function saveFindings(Request $request, string $uuid, SaveReportDraft $save): RedirectResponse
    {
        $data = $request->validate([
            'findings' => ['nullable', 'string', 'max:10000'],
            'recommendations' => ['nullable', 'string', 'max:10000'],
        ]);

        return $this->save($request, $uuid, $save, $data, 'reports.review');
    }

    public function review(Request $request, string $uuid, EntitlementGate $gate, ReportSale $sale): View|RedirectResponse
    {
        return $this->step($request, $uuid, ReportStep::Review, function (Report $report) use ($request, $gate, $sale): array {
            $data = $this->sources->load($report);

            return [
                'data' => $data,
                'blocking' => $data?->blockingEquipment() ?? [],
                'decision' => $gate->decide($this->user($request), Feature::BuildReport),
                'purchased' => $sale->covers($report),
                'sale' => $sale,
            ];
        });
    }

    /**
     * پیش‌نمایش PDF، با همان قالب و همان داده‌ای که صادر خواهد شد — فقط
     * بدون شناسه و QR، و با نشان «پیش‌نمایش» روی هر صفحه. برای کاربر
     * رایگان هم باز است (DEC-29).
     */
    public function preview(Request $request, string $uuid, ReportPdf $pdf): Response|RedirectResponse
    {
        $report = $this->report($request, $uuid);

        if (! $report->isDraft()) {
            return redirect()->route('reports.show', $report->uuid);
        }

        $data = $this->sources->load($report);
        abort_if($data === null, 404, 'منبع این گزارش دیگر در دسترس نیست.');

        $report->loadMissing('supersedes');

        return response($pdf->render(ReportDocument::fromDraft($report, $data)), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview.pdf"',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function issue(Request $request, string $uuid, IssueReport $issue): RedirectResponse
    {
        $report = $this->report($request, $uuid);

        try {
            $issue->handle($report, $this->user($request), $request->boolean('acknowledge_calibration'));
        } catch (EntitlementDenied $denied) {
            return UpgradeRedirect::from($denied);
        } catch (InvalidArgumentException|LogicException $exception) {
            return back()->withErrors(['issue' => $exception->getMessage()]);
        }

        return redirect()->route('reports.show', $report->uuid)
            ->with('status', 'گزارش صادر شد. شناسه رهگیری روی همه صفحه‌های PDF چاپ شده است.');
    }

    /**
     * @param  callable(Report): array<string, mixed>  $extra
     */
    private function step(Request $request, string $uuid, ReportStep $step, callable $extra): View|RedirectResponse
    {
        $report = $this->report($request, $uuid);

        if (! $report->isDraft()) {
            return redirect()->route('reports.show', $report->uuid);
        }

        return view('reports::step-'.strtolower($step->name), [
            'report' => $report,
            'step' => $step,
            'source' => $this->sources->find($report->source_key),
            ...$extra($report),
        ]);
    }

    /** @param  array<string, mixed>  $attributes */
    private function save(Request $request, string $uuid, SaveReportDraft $save, array $attributes, string $next): RedirectResponse
    {
        $report = $this->report($request, $uuid);

        try {
            $save->handle($report, $attributes);
        } catch (LogicException) {
            return redirect()->route('reports.show', $report->uuid);
        }

        return redirect()->route($next, $report->uuid);
    }
}
