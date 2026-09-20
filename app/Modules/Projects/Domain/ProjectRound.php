<?php

declare(strict_types=1);

namespace App\Modules\Projects\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * یک دور اندازه‌گیری — مثلاً «پیش از اقدام کنترلی» و «پس از آن».
 *
 * @property int $id
 * @property int $project_id
 * @property string $title
 * @property Carbon|null $measured_on
 * @property int $sort_order
 */
final class ProjectRound extends Model
{
    protected $fillable = ['project_id', 'title', 'measured_on', 'sort_order'];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<ProjectReading, $this> */
    public function readings(): HasMany
    {
        return $this->hasMany(ProjectReading::class, 'project_round_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['measured_on' => 'date'];
    }
}
