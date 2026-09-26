<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Domain;

use App\Modules\Bundles\Domain\Enums\PurchaseStatus;
use App\Support\Money;
use App\Support\Payments\PaymentSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $bundle_id
 * @property int $user_id
 * @property PurchaseStatus $status
 * @property int $price_toman
 * @property PaymentSource $payment_source
 * @property string|null $gateway_authority
 * @property string|null $gateway_ref_id
 * @property Carbon|null $paid_at
 * @property Carbon $created_at
 */
final class BundlePurchase extends Model
{
    protected $fillable = [
        'uuid',
        'bundle_id',
        'user_id',
        'status',
        'price_toman',
        'payment_source',
        'gateway_authority',
        'gateway_ref_id',
        'paid_at',
    ];

    /** @return BelongsTo<Bundle, $this> */
    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    /** @return HasMany<BundlePurchaseLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(BundlePurchaseLine::class);
    }

    /** @param  Builder<self>  $query */
    public function scopePaid(Builder $query): void
    {
        $query->where('status', PurchaseStatus::Paid->value);
    }

    public function price(): Money
    {
        return Money::toman($this->price_toman);
    }

    public function isPaid(): bool
    {
        return $this->status === PurchaseStatus::Paid;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => PurchaseStatus::class,
            'payment_source' => PaymentSource::class,
            'paid_at' => 'datetime',
        ];
    }
}
