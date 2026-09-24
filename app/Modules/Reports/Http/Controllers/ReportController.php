<?php

declare(strict_types=1);

namespace App\Modules\Reports\Http\Controllers;

use App\Modules\Reports\Actions\ReviseReport;
use App\Modules\Reports\Actions\RevokeReport;
use App\Modules\Reports\Actions\StartReport;
use App\Modules\Reports\Domain\Report;
use App\Modules\Reports\Services\ReportSources;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ReportController
{
    use FindsReports;

    public function __construct(private ReportSources $sources) {}

    public function index(Request $request): View
    {
        return view('reports::index', [
            'reports' => Report::query()
                ->forUser((int) $this->user($request)->getKey())
                ->latest('updated_at')
                ->get(),
        ]);
    }

    /** مرحله اول: انتخاب منبع. */
    public function create(Request $request): View
    {
        $userId = (int) $this->user($request)->getKey();

        $sources = [];

        foreach ($this->sources->all() as $source) {
            $sources[] = ['source' => $source, 'options' => $source->options($userId)];
        }

        // بی‌انتخاب، اولین منبعی که چیزی برای گزارش دارد باز می‌شود.
        $default = collect($sources)->first(static fn (array $entry): bool => $entry['options'] !== []) ?? $sources[0] ?? null;

        return view('reports::create', [
            'sources' => $sources,
            'selected' => (string) $request->query('source', $default === null ? '' : $default['source']->key()),
        ]);
    }

    public function store(Request $request, StartReport $start): RedirectResponse
    {
        $data = $request->validate([
            'source' => ['required', 'string', 'max:32'],
            'references' => ['required', 'array', 'min:1', 'max:50'],
            'references.*' => ['string', 'max:64'],
        ], [
            'references.required' => 'دست‌کم یک مورد را برای گزارش انتخاب کنید.',
        ]);

        try {
            $report = $start->handle($this->user($request), (string) $data['source'], array_values($data['references']));
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['references' => $exception->getMessage()]);
        }

        return redirect()->route('reports.details', $report->uuid);
    }

    public function show(Request $request, string $uuid): View|RedirectResponse
    {
        $report = $this->report($request, $uuid);

        if ($report->isDraft()) {
            return redirect()->route('reports.details', $report->uuid);
        }

        $report->load(['supersedes', 'supersededBy']);

        return view('reports::show', [
            'report' => $report,
            'document' => $report->document(),
            'openRevision' => Report::query()->where('supersedes_id', $report->id)->whereNull('tracking_code')->first(),
        ]);
    }

    /**
     * فایل صادرشده، همان بایت‌هایی که هششان ثبت شده. هرگز از نو ساخته
     * نمی‌شود، وگرنه هش صفحه تأیید با فایل دست کاربر نمی‌خواند.
     */
    public function download(Request $request, string $uuid): StreamedResponse
    {
        $report = $this->report($request, $uuid);
        $disk = Storage::disk((string) config('reports.disk', 'local'));

        abort_if($report->pdf_path === null || ! $disk->exists($report->pdf_path), 404, 'فایل این گزارش پیدا نشد.');

        return $disk->download($report->pdf_path, $report->tracking_code.'.pdf');
    }

    public function revise(Request $request, string $uuid, ReviseReport $revise): RedirectResponse
    {
        try {
            $draft = $revise->handle($this->report($request, $uuid));
        } catch (LogicException $exception) {
            return back()->withErrors(['report' => $exception->getMessage()]);
        }

        return redirect()->route('reports.details', $draft->uuid);
    }

    public function revoke(Request $request, string $uuid, RevokeReport $revoke): RedirectResponse
    {
        $report = $this->report($request, $uuid);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']], [
            'reason.required' => 'دلیل ابطال را بنویسید.',
        ]);

        try {
            $revoke->handle($report, (string) $data['reason'], (int) $this->user($request)->getKey());
        } catch (LogicException $exception) {
            return back()->withErrors(['reason' => $exception->getMessage()]);
        }

        return redirect()->route('reports.show', $report->uuid)
            ->with('status', 'گزارش باطل شد. صفحه تأیید از این پس «باطل‌شده» نشان می‌دهد.');
    }

    /** فقط پیش‌نویس حذف می‌شود؛ گزارش صادرشده سند است و باطل می‌شود، نه حذف. */
    public function destroy(Request $request, string $uuid): RedirectResponse
    {
        $report = $this->report($request, $uuid);

        if (! $report->isDraft()) {
            return back()->withErrors(['report' => 'گزارش صادرشده حذف نمی‌شود؛ در صورت نیاز باطلش کنید.']);
        }

        $report->delete();

        return redirect()->route('reports.index')->with('status', 'پیش‌نویس حذف شد.');
    }
}
