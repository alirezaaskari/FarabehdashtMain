<?php

declare(strict_types=1);

namespace App\Modules\Projects\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Projects\Domain\Project;
use App\Support\Audit\AuditEntry;

/**
 * کاربر پذیرفت که با تجهیز بدون کالیبراسیون معتبر گزارش بگیرد.
 *
 * این یک ادعا درباره اعتبار داده اندازه‌گیری است و باید رد داشته باشد. دفتر
 * رویداد شناسه تجهیزات را می‌نویسد، نه مقادیر اندازه‌گیری.
 */
final readonly class EquipmentWarningAcknowledged implements AuditableEvent
{
    /**
     * @param  list<int>  $equipmentIds
     */
    public function __construct(
        public Project $project,
        public array $equipmentIds,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'projects.equipment_warning_acknowledged',
            subjectType: Project::class,
            subjectId: $this->project->uuid,
            actorId: $this->actorId,
            after: ['equipment_ids' => $this->equipmentIds],
            context: ['project_title' => $this->project->title],
        );
    }
}
