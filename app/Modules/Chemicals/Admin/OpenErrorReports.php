<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Admin;

use App\Contracts\ApprovalQueueSource;
use App\Modules\Chemicals\Domain\Enums\ErrorReportStatus;
use App\Modules\Chemicals\Domain\SubstanceErrorReport;
use App\Modules\Chemicals\Filament\Pages\ChemicalImportPage;
use App\Support\Admin\PendingItem;
use Illuminate\Support\Facades\Route;

/** گزارش اشتباهی که مدیر هنوز بررسی نکرده، در صف کارهای پیشخوان. */
final readonly class OpenErrorReports implements ApprovalQueueSource
{
    /** @return iterable<PendingItem> */
    public function pendingItems(): iterable
    {
        $page = Route::has('filament.fbh.pages.chemical-error-reports')
            ? route('filament.fbh.pages.chemical-error-reports')
            : url('/');

        $reports = SubstanceErrorReport::query()
            ->with('substance:id,name_fa')
            ->where('status', ErrorReportStatus::Open->value)
            ->cursor();

        foreach ($reports as $report) {
            yield new PendingItem(
                ability: ChemicalImportPage::ABILITY,
                kind: 'substance_error_report',
                title: 'گزارش اشتباه — '.$report->substance->name_fa,
                url: $page,
                waitingSince: $report->created_at,
            );
        }
    }
}
