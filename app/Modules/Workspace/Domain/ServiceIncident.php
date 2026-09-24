<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Domain;

use App\Modules\Workspace\Domain\Enums\Service;
use App\Modules\Workspace\Domain\Enums\ServiceState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * یک رویداد اختلال یا نگهداری روی یکی از سرویس‌ها.
 *
 * @property int $id
 * @property string $uuid
 * @property Service $service
 * @property ServiceState $state
 * @property string $title
 * @property string|null $body
 * @property Carbon $started_at
 * @property Carbon|null $resolved_at
 * @property string|null $resolution
 * @property int|null $reported_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class ServiceIncident extends Model
{
    protected $fillable = [
        'uuid',
        'service',
        'state',
        'title',
        'body',
        'started_at',
        'resolved_at',
        'resolution',
        'reported_by',
    ];

    /** @return HasMany<IncidentSubscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(IncidentSubscription::class, 'incident_id');
    }

    /**
     * رویداد باز: شروع شده و هنوز رفع نشده.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeOpen(Builder $query, ?Carbon $at = null): void
    {
        $query->whereNull('resolved_at')->where('started_at', '<=', $at ?? Carbon::now());
    }

    /** @param  Builder<$this>  $query */
    public function scopeUpcoming(Builder $query, ?Carbon $at = null): void
    {
        $query->whereNull('resolved_at')->where('started_at', '>', $at ?? Carbon::now());
    }

    /** @param  Builder<$this>  $query */
    public function scopeOverlapping(Builder $query, Carbon $from, Carbon $to): void
    {
        $query->where('started_at', '<=', $to)
            ->where(fn (Builder $q) => $q->whereNull('resolved_at')->orWhere('resolved_at', '>=', $from));
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function isUpcoming(): bool
    {
        return ! $this->isResolved() && $this->started_at->isFuture();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'service' => Service::class,
            'state' => ServiceState::class,
            'started_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }
}
