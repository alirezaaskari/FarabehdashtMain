<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Domain;

use App\Models\User;
use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * یک محصول دیجیتال (فایل، قالب) یک فروشنده.
 *
 * @property int $id
 * @property string $uuid
 * @property int $vendor_user_id
 * @property string $slug
 * @property string $title
 * @property string|null $description
 * @property int $price_toman
 * @property ProductStatus $status
 * @property string|null $review_note
 * @property Carbon|null $reviewed_at
 * @property int|null $reviewed_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class Product extends Model
{
    protected $fillable = [
        'uuid',
        'vendor_user_id',
        'slug',
        'title',
        'description',
        'price_toman',
        'status',
        'review_note',
        'reviewed_at',
        'reviewed_by',
    ];

    /** @return BelongsTo<User, $this> */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return HasMany<ProductVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(ProductVersion::class)->latest('id');
    }

    public function latestVersion(): ?ProductVersion
    {
        return $this->versions->first();
    }

    public function price(): Money
    {
        return Money::toman($this->price_toman);
    }

    /** @param  Builder<$this>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ProductStatus::Published->value);
    }

    /** @param  Builder<$this>  $query */
    public function scopeOwnedBy(Builder $query, int $vendorUserId): void
    {
        $query->where('vendor_user_id', $vendorUserId);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'price_toman' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }
}
