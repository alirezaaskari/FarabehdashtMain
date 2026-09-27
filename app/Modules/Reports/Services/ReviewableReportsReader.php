<?php

declare(strict_types=1);

namespace App\Modules\Reports\Services;

use App\Contracts\ReviewableReports;
use App\Modules\Reports\Domain\Enums\ReportStatus;
use App\Modules\Reports\Domain\Report;
use App\Modules\Reports\Domain\ReportDocument;
use App\Support\Reporting\ReviewableReport;
use Illuminate\Contracts\View\Factory;

/**
 * پیاده‌سازی {@see ReviewableReports}: فقط از Snapshot صدور می‌خواند، پس
 * آنچه بررسی‌کننده می‌بیند همان است که روی کاغذ رفته.
 */
final readonly class ReviewableReportsReader implements ReviewableReports
{
    public function __construct(private Factory $views) {}

    public function issuedBy(int $userId, int $limit = 20): array
    {
        return Report::query()
            ->forUser($userId)
            ->where('status', ReportStatus::Issued)
            ->latest('issued_at')
            ->limit($limit)
            ->get()
            ->map($this->present(...))
            ->filter()
            ->values()
            ->all();
    }

    public function ownedBy(int $userId, string $uuid): ?ReviewableReport
    {
        $report = Report::query()->forUser($userId)->where('uuid', $uuid)->where('status', ReportStatus::Issued)->first();

        return $report === null ? null : $this->present($report);
    }

    public function find(string $uuid): ?ReviewableReport
    {
        $report = Report::query()->where('uuid', $uuid)->issued()->first();

        return $report === null ? null : $this->present($report);
    }

    private function present(Report $report): ?ReviewableReport
    {
        $document = $report->document();

        if ($document === null || $report->tracking_code === null) {
            return null;
        }

        return new ReviewableReport(
            uuid: $report->uuid,
            title: (string) $report->title,
            trackingCode: $report->tracking_code,
            issuedAt: $report->issued_at ?? $report->created_at,
            valid: $report->status === ReportStatus::Issued,
            sections: self::sections($document),
            html: $this->views->make('reports::partials.document-web', ['document' => $document])->render(),
        );
    }

    /** @return array<string, string> */
    private static function sections(ReportDocument $document): array
    {
        return array_filter([
            'meta' => 'مشخصات گزارش',
            'results' => 'نتایج اندازه‌گیری',
            'method' => $document->includeMethod ? 'روش محاسبه' : null,
            'equipment' => $document->includeEquipment && $document->data->equipment !== [] ? 'تجهیزات به‌کاررفته' : null,
            'findings' => $document->findings ? 'یافته‌ها' : null,
            'recommendations' => $document->recommendations ? 'توصیه‌ها' : null,
        ]);
    }
}
