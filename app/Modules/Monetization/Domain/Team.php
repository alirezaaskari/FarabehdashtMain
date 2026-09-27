<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Domain;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * تیمی که صاحبش چند صندلی Pro خریده (بخش ۱۹-۶).
 *
 * صاحب خودش یک صندلی حساب می‌شود (DEC-61)، پس `seat_count - 1` جا برای عضو
 * هست. مرز revenue-additions: تیم فقط صورتحساب و کتابخانه مشترک است؛ صاحب
 * هیچ دسترسی‌ای به محاسبه، پروژه یا گزارش اعضا ندارد.
 *
 * @property int $id
 * @property string $uuid
 * @property int $owner_id
 * @property string $name
 * @property int $seat_count
 * @property Carbon|null $ends_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $owner
 */
final class Team extends Model
{
    protected $fillable = ['uuid', 'owner_id', 'name'];

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return HasMany<TeamSeat, $this> */
    public function seats(): HasMany
    {
        return $this->hasMany(TeamSeat::class);
    }

    /** @return HasMany<TeamInvitation, $this> */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    /** @return HasMany<TeamPeriod, $this> */
    public function periods(): HasMany
    {
        return $this->hasMany(TeamPeriod::class);
    }

    public function isCurrent(?Carbon $at = null): bool
    {
        return $this->ends_at !== null && $this->ends_at->isAfter($at ?? Carbon::now());
    }

    /** @param  Builder<$this>  $query */
    public function scopeCurrent(Builder $query, ?Carbon $at = null): void
    {
        $query->where('ends_at', '>', $at ?? Carbon::now());
    }

    /** صندلی‌های گرفته‌شده: صاحب، اعضای فعال و دعوت‌های در انتظار. */
    public function usedSeats(): int
    {
        return 1
            + $this->seats()->active()->count()
            + $this->invitations()->pending()->count();
    }

    public function freeSeats(): int
    {
        return max(0, $this->seat_count - $this->usedSeats());
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'seat_count' => 'integer',
            'ends_at' => 'datetime',
        ];
    }
}
