<?php

declare(strict_types=1);

namespace App\Modules\Projects\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * یک ایستگاه اندازه‌گیری — ستون جدول مقایسه.
 *
 * @property int $id
 * @property int $project_id
 * @property string $title
 * @property string|null $description
 * @property int $sort_order
 */
final class ProjectStation extends Model
{
    protected $fillable = ['project_id', 'title', 'description', 'sort_order'];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<ProjectReading, $this> */
    public function readings(): HasMany
    {
        return $this->hasMany(ProjectReading::class, 'project_station_id');
    }
}
