<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Domain;

use App\Modules\Webinars\Domain\Enums\WebinarStatus;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property string $slug
 * @property string $title
 * @property string $description
 * @property string $instructor_name
 * @property Carbon $starts_at
 * @property int $duration_minutes
 * @property int $capacity
 * @property int $price_toman
 * @property string $join_url
 * @property string|null $recording_url
 * @property WebinarStatus $status
 * @property Carbon $updated_at
 */
final class Webinar extends Model
{
    protected $fillable = [
        'uuid',
        'slug',
        'title',
        'description',
        'instructor_name',
        'starts_at',
        'duration_minutes',
        'capacity',
        'price_toman',
        'join_url',
        'recording_url',
        'status',
    ];

    protected $hidden = ['join_url'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return HasMany<WebinarRegistration, $this> */
    public function registrations(): HasMany
    {
        return $this->hasMany(WebinarRegistration::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', WebinarStatus::Published);
    }

    public function price(): Money
    {
        return Money::toman($this->price_toman);
    }

    public function isFree(): bool
    {
        return $this->price_toman === 0;
    }

    public function isPublished(): bool
    {
        return $this->status === WebinarStatus::Published;
    }

    public function endsAt(): CarbonInterface
    {
        return $this->starts_at->copy()->addMinutes($this->duration_minutes);
    }

    public function joinOpensAt(): CarbonInterface
    {
        return $this->starts_at->copy()->subMinutes((int) config('webinars.join_opens_minutes', 60));
    }

    public function hasStarted(): bool
    {
        return $this->starts_at->isPast();
    }

    public function hasEnded(): bool
    {
        return $this->endsAt()->isPast();
    }

    /** ورود فقط از یک ساعت پیش از شروع تا پایان جلسه باز است. */
    public function isJoinOpen(): bool
    {
        return now()->between($this->joinOpensAt(), $this->endsAt());
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'duration_minutes' => 'integer',
            'capacity' => 'integer',
            'price_toman' => 'integer',
            'join_url' => 'encrypted',
            'status' => WebinarStatus::class,
        ];
    }
}
