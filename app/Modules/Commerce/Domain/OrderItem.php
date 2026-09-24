<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Domain;

use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * یک ردیف سفارش — قیمت، فروشنده و نرخ کمیسیون Snapshot لحظه خرید.
 *
 * مبلغ بازگشتی این‌جا کش نمی‌شود؛ همیشه از جمع {@see Refund} همین ردیف
 * محاسبه می‌شود (توضیح در مهاجرت جدول).
 *
 * `unit_price_toman` مبلغ **پرداخت‌شده** است، نه قیمت فهرست: تخفیف مشترک
 * پیش از ثبت از آن کم شده و در `discount_toman` ثبت می‌شود.
 *
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property int $vendor_user_id
 * @property int $unit_price_toman
 * @property int $discount_toman
 * @property int $commission_rate_bp
 * @property int $commission_toman
 * @property int $vendor_amount_toman
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'vendor_user_id',
        'unit_price_toman',
        'discount_toman',
        'commission_rate_bp',
        'commission_toman',
        'vendor_amount_toman',
    ];

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<User, $this> */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_user_id');
    }

    /** @return HasMany<Refund, $this> */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function unitPrice(): Money
    {
        return Money::toman($this->unit_price_toman);
    }

    /**
     * تخفیف مشترک روی این ردیف.
     *
     * فقط برای نمایش و حسابرسی است: `unit_price_toman` از قبل مبلغ پس از
     * تخفیف است، پس هیچ محاسبه‌ای نباید این عدد را دوباره کم کند.
     */
    public function discount(): Money
    {
        return Money::toman($this->discount_toman);
    }

    public function vendorAmount(): Money
    {
        return Money::toman($this->vendor_amount_toman);
    }

    public function commission(): Money
    {
        return Money::toman($this->commission_toman);
    }

    public function refundedAmount(): Money
    {
        return Money::toman((int) $this->refunds()->sum('amount_toman'));
    }

    /** بیشینه مبلغی که هنوز می‌شود از این ردیف برگرداند. */
    public function remainingRefundable(): Money
    {
        return $this->unitPrice()->minus($this->refundedAmount());
    }

    public function isFullyRefunded(): bool
    {
        return $this->remainingRefundable()->isZero();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'unit_price_toman' => 'integer',
            'discount_toman' => 'integer',
            'commission_rate_bp' => 'integer',
            'commission_toman' => 'integer',
            'vendor_amount_toman' => 'integer',
        ];
    }
}
