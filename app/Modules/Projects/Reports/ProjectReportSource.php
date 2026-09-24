<?php

declare(strict_types=1);

namespace App\Modules\Projects\Reports;

use App\Contracts\CalculationReader;
use App\Contracts\ReportSource;
use App\Contracts\ToolDirectory;
use App\Modules\Projects\Domain\Equipment;
use App\Modules\Projects\Domain\Project;
use App\Modules\Projects\Domain\ProjectReading;
use App\Support\JalaliDate;
use App\Support\Measurement\MeasurementNumber;
use App\Support\PersianDigits;
use App\Support\Reporting\ReportData;
use App\Support\Reporting\ReportEquipment;
use App\Support\Reporting\ReportMeasurement;
use App\Support\Reporting\ReportSourceOption;
use Illuminate\Contracts\Container\Container;

/**
 * پروژه اندازه‌گیری به‌عنوان منبع گزارش.
 *
 * همه دورها و ایستگاه‌ها با هم می‌آیند؛ جدول نتایج گزارش همان جدول مقایسه
 * صفحه پروژه است. مشخصات هر تجهیزی که در قرائتی به‌کار رفته خودکار ضمیمه
 * می‌شود و وضعیت کالیبراسیونش را همین ماژول تعیین می‌کند (بخش ۸).
 */
final readonly class ProjectReportSource implements ReportSource
{
    public function __construct(private Container $container) {}

    public function key(): string
    {
        return 'project';
    }

    public function label(): string
    {
        return 'پروژه اندازه‌گیری';
    }

    public function multiple(): bool
    {
        return false;
    }

    public function options(int $userId): array
    {
        return Project::query()
            ->forUser($userId)
            ->whereHas('readings')
            ->withCount('readings')
            ->latest('updated_at')
            ->get()
            ->map(static fn (Project $project): ReportSourceOption => new ReportSourceOption(
                reference: $project->uuid,
                title: $project->title,
                meta: trim(($project->client_name ? $project->client_name.' · ' : '')
                    .PersianDigits::from($project->readings_count).' قرائت'),
            ))
            ->values()
            ->all();
    }

    public function load(int $userId, array $references): ?ReportData
    {
        $project = Project::query()
            ->forUser($userId)
            ->where('uuid', $references[0] ?? '')
            ->first();

        if ($project === null) {
            return null;
        }

        $rounds = $project->rounds()->get()->keyBy('id');
        $stations = $project->stations()->get()->keyBy('id');
        $readings = $project->readings()->with('equipment')->get()
            ->sortBy(static fn (ProjectReading $r): array => [
                $rounds->keys()->search($r->project_round_id),
                $stations->keys()->search($r->project_station_id),
            ]);

        $measurements = [];
        $equipment = [];

        foreach ($readings as $reading) {
            $round = $rounds->get($reading->project_round_id);

            $measurements[] = new ReportMeasurement(
                group: $round->title ?? '',
                point: $stations->get($reading->project_station_id)->title ?? '',
                parameter: $this->toolTitle($reading->tool_slug),
                value: MeasurementNumber::format($reading->value),
                unit: $reading->unit,
                formula: $this->formula($reading, $userId),
                equipmentId: $reading->equipment_id,
                measuredOn: $round?->measured_on === null ? null : JalaliDate::short($round->measured_on),
            );

            if ($reading->equipment !== null) {
                $equipment[$reading->equipment->id] ??= $this->equipment($reading->equipment);
            }
        }

        return new ReportData(
            sourceTitle: $project->title,
            measurements: $measurements,
            equipment: array_values($equipment),
            suggestedTitle: 'گزارش '.$project->title,
            suggestedClient: $project->client_name,
        );
    }

    private function equipment(Equipment $equipment): ReportEquipment
    {
        $status = $equipment->calibrationStatus();

        return new ReportEquipment(
            id: $equipment->id,
            name: $equipment->name,
            manufacturer: $equipment->manufacturer,
            model: $equipment->model,
            serialNumber: $equipment->serial_number,
            accuracyClass: $equipment->accuracy_class,
            calibratedOn: $equipment->calibrated_on === null ? null : JalaliDate::short($equipment->calibrated_on),
            validUntil: $equipment->calibration_valid_until === null ? null : JalaliDate::short($equipment->calibration_valid_until),
            calibrationReference: $equipment->calibration_reference,
            calibrationStatus: $status->label(),
            blocking: $status->blocksReport(),
        );
    }

    /**
     * نسخه فرمول از قرارداد ماژول ابزارها؛ اگر خاموش باشد، قرائت بدون فرمول
     * چاپ می‌شود — همان رفتار قرائت دستی.
     */
    private function formula(ProjectReading $reading, int $userId): ?string
    {
        if ($reading->calculation_uuid === null || ! $this->container->bound(CalculationReader::class)) {
            return null;
        }

        $calculation = $this->container->make(CalculationReader::class)->findForUser($reading->calculation_uuid, $userId);

        return $calculation === null ? null : $calculation->formulaId.' v'.$calculation->formulaVersion;
    }

    private function toolTitle(?string $slug): ?string
    {
        if ($slug === null || ! $this->container->bound(ToolDirectory::class)) {
            return null;
        }

        return $this->container->make(ToolDirectory::class)->find($slug)?->title;
    }
}
