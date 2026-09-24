<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Domain;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * صندلی‌ای که یک اشتراک به حساب مستقل یک عضو می‌دهد.
 *
 * مرز صریح `docs/roadmap/revenue-additions.md`: صندلی تیمی فقط صورتحساب و
 * اشتراک‌گذاری است. عضو حساب خودش را دارد، داده‌اش مال خودش است، و صاحب
 * اشتراک هیچ دسترسی‌ای به آن پیدا نمی‌کند — فقط دسترسی Pro را به او می‌دهد
 * و می‌تواند پس بگیرد.
 *
 * صندلی باطل‌شده پاک نمی‌شود: `revoked_at` پر می‌شود تا تاریخچه بماند.
 *
 * @property int $id
 * @property int $subscription_id
 * @property int $member_user_id
 * @property int|null $granted_by
 * @property Carbon $granted_at
 * @property Carbon|null $revoked_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class TeamSeat extends Model
{
    protected $fillable = [
        'subscription_id',
        'member_user_id',
        'granted_by',
        'granted_at',
        'revoked_at',
    ];

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /** @return BelongsTo<User, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_user_id');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    /** @param  Builder<$this>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('revoked_at');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
