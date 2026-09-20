<?php

declare(strict_types=1);

namespace App\Modules\Projects\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * قرائت یک ایستگاه در یک دور — خانه جدول مقایسه.
 *
 * مقدار حتی وقتی از یک محاسبه آمده باشد همین‌جا ذخیره می‌شود. وابسته
 * نگه‌داشتنش به ماژول ابزارها یعنی اگر روزی آن ماژول خاموش شود، پروژه‌های
 * قدیمی عددشان را از دست بدهند.
 *
 * @property int $id
 * @property int $project_id
 * @property int $project_round_id
 * @property int $project_station_id
 * @property int|null $equipment_id
 * @property float $value
 * @property string $unit
 * @property string|null $calculation_uuid
 * @property string|null $tool_slug
 * @property string|null $notes
 */
final class ProjectReading extends Model
{
    protected $fillable = [
        'project_id',
        'project_round_id',
        'project_station_id',
        'equipment_id',
        'value',
        'unit',
        'calculation_uuid',
        'tool_slug',
        'notes',
    ];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<ProjectRound, $this> */
    public function round(): BelongsTo
    {
        return $this->belongsTo(ProjectRound::class, 'project_round_id');
    }

    /** @return BelongsTo<ProjectStation, $this> */
    public function station(): BelongsTo
    {
        return $this->belongsTo(ProjectStation::class, 'project_station_id');
    }

    /** @return BelongsTo<Equipment, $this> */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['value' => 'float'];
    }
}
