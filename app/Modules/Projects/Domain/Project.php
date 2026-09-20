<?php

declare(strict_types=1);

namespace App\Modules\Projects\Domain;

use App\Models\User;
use App\Modules\Projects\Domain\Enums\Industry;
use App\Modules\Projects\Domain\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * یک پروژه اندازه‌گیری.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string $title
 * @property string|null $client_name
 * @property Industry|null $industry
 * @property ProjectStatus $status
 * @property Carbon|null $started_on
 * @property string|null $notes
 * @property Carbon|null $equipment_warning_acknowledged_at
 */
final class Project extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'title',
        'client_name',
        'industry',
        'status',
        'started_on',
        'notes',
        'equipment_warning_acknowledged_at',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<ProjectStation, $this> */
    public function stations(): HasMany
    {
        return $this->hasMany(ProjectStation::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasMany<ProjectRound, $this> */
    public function rounds(): HasMany
    {
        return $this->hasMany(ProjectRound::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasMany<ProjectReading, $this> */
    public function readings(): HasMany
    {
        return $this->hasMany(ProjectReading::class);
    }

    /** @param  Builder<$this>  $query */
    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    public function editable(): bool
    {
        return $this->status->editable();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'industry' => Industry::class,
            'status' => ProjectStatus::class,
            'started_on' => 'date',
            'equipment_warning_acknowledged_at' => 'datetime',
        ];
    }
}
