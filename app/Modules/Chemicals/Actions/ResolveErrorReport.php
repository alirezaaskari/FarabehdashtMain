<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Actions;

use App\Modules\Chemicals\Domain\Enums\ErrorReportStatus;
use App\Modules\Chemicals\Domain\SubstanceErrorReport;
use App\Modules\Chemicals\Events\SubstanceErrorReportResolved;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;

/**
 * مدیر گزارش اشتباه را می‌بندد.
 *
 * «اصلاح شد» یعنی مدیر ماده را از ویرایشگر درست کرده؛ این کلاس داده ماده را
 * دست نمی‌زند، چون هر تغییر عدد باید از ویرایشگر و با نسخه و منبع بگذرد.
 */
final readonly class ResolveErrorReport
{
    public function __construct(private Dispatcher $events) {}

    public function handle(SubstanceErrorReport $report, ErrorReportStatus $outcome, int $actorId, ?string $note = null): void
    {
        if ($outcome === ErrorReportStatus::Open) {
            throw new InvalidArgumentException('A report is closed as fixed or dismissed.');
        }

        if ($report->status !== ErrorReportStatus::Open) {
            return;
        }

        $note = $note === null ? null : (trim($note) === '' ? null : mb_substr(trim($note), 0, 500));

        $report->forceFill([
            'status' => $outcome,
            'admin_note' => $note,
            'resolved_by' => $actorId,
            'resolved_at' => now(),
        ])->save();

        $this->events->dispatch(new SubstanceErrorReportResolved($report, $actorId));
    }
}
