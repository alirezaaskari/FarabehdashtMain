<?php

declare(strict_types=1);

namespace App\Modules\Tools\Reports;

use App\Contracts\ReportSource;
use App\Modules\Tools\Domain\SavedCalculation;
use App\Modules\Tools\Services\ResultPresenter;
use App\Modules\Tools\Services\ToolCatalog;
use App\Support\JalaliDate;
use App\Support\Reporting\ReportData;
use App\Support\Reporting\ReportMeasurement;
use App\Support\Reporting\ReportSourceOption;

/**
 * چند محاسبه ذخیره‌شده به‌عنوان منبع گزارش — «همین ۵ محاسبه را یک گزارش کن».
 *
 * هر خروجی محاسبه یک سطر جدول می‌شود، با شناسه و نسخه فرمول لحظه ثبت
 * (ADR-0005). محاسبه تجهیز ندارد، پس پیوست تجهیزات خالی است.
 */
final readonly class CalculationReportSource implements ReportSource
{
    private const OPTIONS_LIMIT = 100;

    public function __construct(
        private ToolCatalog $catalog,
        private ResultPresenter $presenter,
    ) {}

    public function key(): string
    {
        return 'calculations';
    }

    public function label(): string
    {
        return 'محاسبه‌های ذخیره‌شده';
    }

    public function multiple(): bool
    {
        return true;
    }

    public function options(int $userId): array
    {
        return SavedCalculation::query()
            ->forUser($userId)
            ->latest('id')
            ->limit(self::OPTIONS_LIMIT)
            ->get()
            ->map(fn (SavedCalculation $calculation): ReportSourceOption => new ReportSourceOption(
                reference: $calculation->uuid,
                title: $calculation->label ?? $this->toolTitle($calculation->tool_slug),
                meta: $this->toolTitle($calculation->tool_slug).' · '.JalaliDate::short($calculation->created_at),
            ))
            ->values()
            ->all();
    }

    public function load(int $userId, array $references): ?ReportData
    {
        $calculations = SavedCalculation::query()
            ->forUser($userId)
            ->whereIn('uuid', $references)
            ->oldest('id')
            ->get();

        if ($calculations->isEmpty()) {
            return null;
        }

        $measurements = [];

        foreach ($calculations as $calculation) {
            $group = $this->toolTitle($calculation->tool_slug);
            $date = JalaliDate::short($calculation->created_at);

            foreach ($this->presenter->fromStored($calculation->outputs) as $row) {
                $measurements[] = new ReportMeasurement(
                    group: $group,
                    point: $calculation->label ?? $date,
                    parameter: $row->label,
                    value: $row->value,
                    unit: $row->unit,
                    formula: $calculation->formula_id.' v'.$calculation->formula_version,
                    measuredOn: $date,
                );
            }
        }

        return new ReportData(
            sourceTitle: $calculations->count() === 1
                ? ($calculations->first()->label ?? $this->toolTitle($calculations->first()->tool_slug))
                : 'محاسبه‌های ذخیره‌شده',
            measurements: $measurements,
        );
    }

    private function toolTitle(string $slug): string
    {
        return $this->catalog->has($slug) ? $this->catalog->resolve($slug)->definition->title : $slug;
    }
}
