<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Domain;

use App\Modules\Bundles\Domain\Enums\BundleStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * بسته راه‌حل: چند فایل، دوره و ماه Pro با یک قیمت (DEC-47، فقط مدیر می‌سازد).
 *
 * @property int $id
 * @property string $uuid
 * @property string $slug
 * @property string $title
 * @property string $description
 * @property int $price_toman
 * @property BundleStatus $status
 * @property Carbon|null $published_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class Bundle extends Model
{
    protected $fillable = ['uuid', 'slug', 'title', 'description', 'price_toman', 'status', 'published_at'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return HasMany<BundleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(BundleItem::class)->orderBy('sort');
    }

    /** @param  Builder<self>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', BundleStatus::Published->value);
    }

    public function price(): Money
    {
        return Money::toman($this->price_toman);
    }

    public function isPublished(): bool
    {
        return $this->status === BundleStatus::Published;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => BundleStatus::class,
            'published_at' => 'datetime',
        ];
    }
}
