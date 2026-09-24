<?php

declare(strict_types=1);

namespace App\Modules\Reports\Actions;

use App\Modules\Reports\Domain\Enums\ReportStatus;
use App\Modules\Reports\Domain\Report;
use App\Modules\Reports\Events\ReportRevoked;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;
use LogicException;

/**
 * ابطال گزارش صادرشده.
 *
 * فایل و Snapshot حذف نمی‌شوند: صفحه تأیید باید همچنان بگوید «این سند بوده
 * و باطل شده»، نه «پیدا نشد» — دومی برای گیرنده یعنی سند جعلی است.
 */
final readonly class RevokeReport
{
    public function __construct(private Dispatcher $events) {}

    public function handle(Report $report, string $reason, ?int $actorId): Report
    {
        if (! in_array($report->status, [ReportStatus::Issued, ReportStatus::Superseded], true)) {
            throw new LogicException('فقط گزارش صادرشده باطل می‌شود.');
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('دلیل ابطال را بنویسید.');
        }

        $report->status = ReportStatus::Revoked;
        $report->revoked_at = now();
        $report->revoke_reason = mb_substr($reason, 0, 255);
        $report->revoked_by = $actorId;
        $report->save();

        $this->events->dispatch(new ReportRevoked($report, $actorId));

        return $report;
    }
}
