<?php

declare(strict_types=1);

namespace App\Modules\Reports\Actions;

use App\Modules\Reports\Domain\Enums\ReportStatus;
use App\Modules\Reports\Domain\Report;
use Illuminate\Support\Str;
use LogicException;

/**
 * اصلاح گزارش صادرشده: پیش‌نویس تازه‌ای با همان منبع و متن.
 *
 * نسخه قبلی تا صدور نسخه تازه «صادرشده» می‌ماند؛ پیش‌نویسی که هرگز صادر
 * نشود نباید سند معتبر کسی را از اعتبار بیندازد. اگر پیش‌نویس اصلاحی از
 * قبل باز باشد، همان برمی‌گردد — دو اصلاح موازی یک سند معنا ندارد.
 */
final readonly class ReviseReport
{
    public function handle(Report $report): Report
    {
        if ($report->status !== ReportStatus::Issued) {
            throw new LogicException('فقط آخرین نسخه صادرشده و باطل‌نشده اصلاح می‌شود.');
        }

        $open = Report::query()
            ->where('supersedes_id', $report->id)
            ->where('status', ReportStatus::Draft->value)
            ->first();

        return $open ?? Report::query()->create([
            ...$report->only([
                'user_id', 'source_key', 'source_references', 'title', 'client_name', 'site',
                'measured_on', 'author_name', 'findings', 'recommendations', 'include_equipment', 'include_method',
            ]),
            'uuid' => (string) Str::uuid7(),
            'status' => ReportStatus::Draft,
            'revision' => $report->revision + 1,
            'supersedes_id' => $report->id,
        ]);
    }
}
