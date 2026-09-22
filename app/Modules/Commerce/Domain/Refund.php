<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Domain;

use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * یک بازگشت وجه ثبت‌شده — فقط افزودنی.
 *
 * @property int $id
 * @property string $uuid
 * @property int $order_item_id
 * @property int $amount_toman
 * @property string|null $reason
 * @property int|null $created_by
 * @property Carbon $created_at
 */
final class Refund extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'uuid',
        'order_item_id',
        'amount_toman',
        'reason',
        'created_by',
    ];

    /** @return BelongsTo<OrderItem, $this> */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function amount(): Money
    {
        return Money::toman($this->amount_toman);
    }

    /** بازگشت وجه ثبت‌شده هرگز تغییر نمی‌کند. */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new RuntimeException('بازگشت وجه ثبت‌شده تغییرناپذیر است.');
    }

    /** بازگشت وجه ثبت‌شده هرگز حذف نمی‌شود. */
    public function delete(): bool
    {
        throw new RuntimeException('بازگشت وجه فقط افزودنی است؛ حذف نمی‌شود.');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount_toman' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
