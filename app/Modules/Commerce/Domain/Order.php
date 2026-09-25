<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Domain;

use App\Models\User;
use App\Modules\Commerce\Domain\Enums\OrderStatus;
use App\Support\Money;
use App\Support\Payments\PaymentSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * یک سفارش.
 *
 * پیش از `Paid` هیچ اثر مالی در دفتر کل ندارد؛ ردیف دفتر کل فقط پس از تأیید
 * درگاه نوشته می‌شود.
 *
 * @property int $id
 * @property string $uuid
 * @property int $buyer_user_id
 * @property OrderStatus $status
 * @property int $total_toman
 * @property string|null $gateway_authority
 * @property string|null $gateway_ref_id
 * @property PaymentSource $payment_source
 * @property Carbon|null $paid_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class Order extends Model
{
    protected $fillable = [
        'uuid',
        'buyer_user_id',
        'status',
        'total_toman',
        'gateway_authority',
        'gateway_ref_id',
        'payment_source',
        'paid_at',
    ];

    /** @return BelongsTo<User, $this> */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function total(): Money
    {
        return Money::toman($this->total_toman);
    }

    /** @param  Builder<$this>  $query */
    public function scopeForBuyer(Builder $query, int $buyerUserId): void
    {
        $query->where('buyer_user_id', $buyerUserId);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_source' => PaymentSource::class,
            'total_toman' => 'integer',
            'paid_at' => 'datetime',
        ];
    }
}
