<?php

declare(strict_types=1);

namespace App\Modules\Projects\Services;

use App\Modules\Projects\Domain\Enums\CalibrationStatus;
use App\Modules\Projects\Domain\EquipmentWarning;
use App\Modules\Projects\Domain\Project;
use App\Modules\Projects\Domain\ProjectReading;
use App\Support\JalaliDate;

/**
 * آیا این پروژه آماده صدور گزارش است؟
 *
 * معیار پذیرش بخش ۸: «تجهیز منقضی پیش از صدور گزارش هشدار می‌دهد.»
 *
 * دو چیز که عمداً این‌طور شده‌اند:
 *
 * ۱. **هشدار جلوی کار را نمی‌گیرد، تأیید می‌خواهد.** کارشناسی که می‌داند
 *    دستگاهش دیروز منقضی شده و باز هم می‌خواهد گزارش بدهد، باید بتواند —
 *    ولی با ثبت اینکه خبر داشته. مسدودکردن کامل یعنی کاربر می‌رود تاریخ
 *    کالیبراسیون را الکی عوض می‌کند، و آن بدتر است.
 *
 * ۲. **تأیید با هر تغییر باطل می‌شود.** اگر پس از تأیید، تجهیز تازه‌ای به
 *    پروژه اضافه شود، تأیید قبلی دیگر معتبر نیست؛ وگرنه یک بار تأییدکردن،
 *    همه هشدارهای آینده را هم خاموش می‌کرد.
 */
final readonly class ReportReadiness
{
    /**
     * @return list<EquipmentWarning>
     */
    public function warnings(Project $project): array
    {
        $warningDays = (int) config('projects.calibration_warning_days', 30);

        $readings = $project->readings()
            ->with('equipment')
            ->whereNotNull('equipment_id')
            ->get();

        $warnings = [];

        foreach ($readings->groupBy('equipment_id') as $group) {
            /** @var ProjectReading $first */
            $first = $group->first();
            $equipment = $first->equipment;

            if ($equipment === null) {
                continue;
            }

            $status = $equipment->calibrationStatus($warningDays);

            if ($status->blocksReport() || $status === CalibrationStatus::ExpiringSoon) {
                $warnings[] = new EquipmentWarning(
                    equipmentId: (int) $equipment->getKey(),
                    identification: $equipment->identification(),
                    status: $status,
                    validUntil: $equipment->calibration_valid_until === null
                        ? null
                        : JalaliDate::short($equipment->calibration_valid_until),
                    readingCount: $group->count(),
                );
            }
        }

        return $warnings;
    }

    /**
     * @return list<EquipmentWarning>
     */
    public function blockingWarnings(Project $project): array
    {
        return array_values(array_filter(
            $this->warnings($project),
            static fn (EquipmentWarning $warning): bool => $warning->blocking(),
        ));
    }

    /**
     * گزارش بدون تأیید صریح صادر نمی‌شود وقتی هشدار مسدودکننده هست.
     */
    public function requiresAcknowledgement(Project $project): bool
    {
        if ($this->blockingWarnings($project) === []) {
            return false;
        }

        $acknowledged = $project->equipment_warning_acknowledged_at;

        if ($acknowledged === null) {
            return true;
        }

        // تأییدی که پیش از آخرین تغییر فهرست تجهیزات پروژه ثبت شده، دیگر
        // درباره وضعیت فعلی چیزی نمی‌گوید.
        $lastChange = $project->readings()->max('updated_at');

        return $lastChange !== null && $acknowledged->lt($lastChange);
    }

    public function ready(Project $project): bool
    {
        return ! $this->requiresAcknowledgement($project);
    }
}
