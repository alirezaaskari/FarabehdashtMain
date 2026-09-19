<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain;

use App\Models\User;
use App\Modules\Identity\Database\Factories\UserProfileFactory;
use App\Modules\Identity\Domain\Enums\ProfileStatus;
use App\Modules\Identity\Domain\Enums\ProfileType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * یک نقش تجاری روی حساب کاربر.
 *
 * غیرفعال‌کردن پروفایل هرگز رکورد را حذف نمی‌کند؛ فقط وضعیتش عوض می‌شود.
 *
 * @property int $id
 * @property int $user_id
 * @property ProfileType $type
 * @property ProfileStatus $status
 * @property Carbon|null $requested_at
 * @property Carbon|null $approved_at
 * @property int|null $approved_by
 * @property string|null $rejection_note
 * @property array<string, mixed>|null $meta
 */
final class UserProfile extends Model
{
    /** @use HasFactory<UserProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'requested_at',
        'approved_at',
        'approved_by',
        'rejection_note',
        'meta',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @param  Builder<$this>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', ProfileStatus::Active->value);
    }

    /** @param  Builder<$this>  $query */
    public function scopeAwaitingReview(Builder $query): void
    {
        $query->where('status', ProfileStatus::Pending->value);
    }

    public function grantsAccess(): bool
    {
        return $this->status->grantsAccess();
    }

    protected static function newFactory(): UserProfileFactory
    {
        return UserProfileFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => ProfileType::class,
            'status' => ProfileStatus::class,
            'meta' => 'array',
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }
}
