<?php

declare(strict_types=1);

namespace App\Modules\Projects\Actions;

use App\Modules\Projects\Domain\EquipmentWarning;
use App\Modules\Projects\Domain\Project;
use App\Modules\Projects\Events\EquipmentWarningAcknowledged;
use App\Modules\Projects\Services\ReportReadiness;
use Illuminate\Contracts\Events\Dispatcher;
use RuntimeException;

/**
 * ثبت اینکه کاربر هشدار کالیبراسیون را دیده و با علم به آن ادامه می‌دهد.
 *
 * تأیید بی‌هشدار پذیرفته نمی‌شود: اگر هیچ تجهیز مشکل‌داری نباشد، چیزی برای
 * تأییدکردن نیست و ثبتش فقط دفتر رویداد را شلوغ می‌کند.
 */
final readonly class AcknowledgeEquipmentWarning
{
    public function __construct(
        private ReportReadiness $readiness,
        private Dispatcher $events,
    ) {}

    public function handle(Project $project, ?int $actorId): Project
    {
        $warnings = $this->readiness->blockingWarnings($project);

        if ($warnings === []) {
            throw new RuntimeException('هشدار کالیبراسیونی برای تأیید وجود ندارد.');
        }

        $project->equipment_warning_acknowledged_at = now();
        $project->save();

        $this->events->dispatch(new EquipmentWarningAcknowledged(
            $project,
            array_map(static fn (EquipmentWarning $w): int => $w->equipmentId, $warnings),
            $actorId,
        ));

        return $project;
    }
}
