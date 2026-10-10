<?php

declare(strict_types=1);

namespace App\Modules\Tools\Reports;

use App\Contracts\ReportSource;
use App\Modules\Tools\Domain\ResolvedTool;
use App\Modules\Tools\Domain\SavedCalculation;
use App\Modules\Tools\Services\ResultPresenter;
use App\Modules\Tools\Services\ToolCatalog;
use App\Support\JalaliDate;
use App\Support\Reporting\ReportAssessment;
use App\Support\Reporting\ReportData;
use App\Support\Reporting\ReportMeasurement;
use App\Support\Reporting\ReportSourceOption;

/**
 * چند محاسبه ذخیره‌شده به‌عنوان منبع گزارش — «همین ۵ محاسبه را یک گزارش کن».
 *
 * هر خروجی محاسبه یک سطر جدول می‌شود، با شناسه و نسخه فرمول لحظه ثبت
 * (ADR-0005). محاسبه تجهیز ندارد، پس پیوست تجهیزات خالی است.
 *
 * ارزیابی پوسچر افزون بر امتیازها، شرحی در بخش «ارزیابی ارگونومی» می‌گیرد:
 * تفسیر نتیجه و وضعیتی که ارزیاب ثبت کرده. بی آن، «امتیاز ۷» در گزارش
 * نمی‌گوید چه چیزی در آن ایستگاه دیده شده.
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
            ->listed()
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
        $assessments = [];

        foreach ($calculations as $calculation) {
            $group = $this->toolTitle($calculation->tool_slug);
            $date = JalaliDate::short($calculation->created_at);
            $tool = $this->catalog->has($calculation->tool_slug) ? $this->catalog->resolve($calculation->tool_slug) : null;

            if ($tool?->definition->isPostureAssessment()) {
                $assessments[] = $this->assessment($tool, $calculation, $date);
            }

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
            assessments: $assessments,
        );
    }

    private function assessment(ResolvedTool $tool, SavedCalculation $calculation, string $date): ReportAssessment
    {
        $answers = [];

        foreach ($this->presenter->fromStored($calculation->inputs, $tool->definition->choiceLabels()) as $row) {
            // کلید خاموش یعنی «این مورد دیده نشد»؛ فهرست کردنش گزارش را بلند می‌کند و چیزی نمی‌گوید.
            if ($tool->definition->isToggle($row->key) && ! $this->switchedOn($calculation->inputs[$row->key] ?? null)) {
                continue;
            }

            $answers[] = ['label' => $row->label, 'value' => $row->value];
        }

        return new ReportAssessment(
            method: $tool->definition->title,
            point: $calculation->label ?? $date,
            notes: $calculation->notes,
            answers: $answers,
            measuredOn: $date,
        );
    }

    private function switchedOn(mixed $stored): bool
    {
        return is_array($stored) && (float) ($stored['value'] ?? 0) !== 0.0;
    }

    private function toolTitle(string $slug): string
    {
        return $this->catalog->has($slug) ? $this->catalog->resolve($slug)->definition->title : $slug;
    }
}
