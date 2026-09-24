<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Domain;

use App\Models\User;
use App\Modules\Monetization\Domain\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * اشتراک یک کاربر.
 *
 * دسترسی از `ends_at` خوانده می‌شود، نه از وضعیت تنها: کاربری که اشتراکش را
 * لغو کرده تا پایان دوره‌ای که پولش را داده مشترک است. همین قاعده، سیاست
 * پیش‌فرض خاموشی (`RunToEnd`) را هم بدون کد جداگانه درست می‌کند.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int|null $plan_id
 * @property SubscriptionStatus $status
 * @property Carbon|null $started_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $cancelled_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class Subscription extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'plan_id',
        'status',
        'started_at',
        'ends_at',
        'cancelled_at',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** @return HasMany<SubscriptionPeriod, $this> */
    public function periods(): HasMany
    {
        return $this->hasMany(SubscriptionPeriod::class);
    }

    /** @return HasMany<TeamSeat, $this> */
    public function seats(): HasMany
    {
        return $this->hasMany(TeamSeat::class);
    }

    public function isCurrent(?Carbon $at = null): bool
    {
        if (! $this->status->grantsAccessUntilEnd()) {
            return false;
        }

        return $this->ends_at !== null && $this->ends_at->isAfter($at ?? Carbon::now());
    }

    /** @param  Builder<$this>  $query */
    public function scopeCurrent(Builder $query, ?Carbon $at = null): void
    {
        $query
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Cancelled->value])
            ->where('ends_at', '>', $at ?? Carbon::now());
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'started_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
