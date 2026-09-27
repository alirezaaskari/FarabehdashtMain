<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Domain;

use App\Modules\Monetization\Domain\Enums\BillingCycle;
use App\Modules\Monetization\Domain\Enums\PeriodStatus;
use App\Support\Money;
use App\Support\Payments\PaymentSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * یک دوره پرداخت‌شده (یا در انتظار پرداخت) از یک اشتراک.
 *
 * همان نقشی را برای اشتراک دارد که سفارش برای فروشگاه: پیش از تأیید درگاه
 * هیچ ردیف دفتر کلی نمی‌خورد و هیچ روزی به اشتراک اضافه نمی‌کند.
 *
 * @property int $id
 * @property string $uuid
 * @property int $subscription_id
 * @property int $plan_id
 * @property BillingCycle $billing_cycle
 * @property int $price_toman
 * @property PeriodStatus $status
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property string|null $gateway_authority
 * @property string|null $gateway_ref_id
 * @property PaymentSource $payment_source
 * @property Carbon|null $paid_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class SubscriptionPeriod extends Model
{
    protected $fillable = [
        'uuid',
        'subscription_id',
        'plan_id',
        'billing_cycle',
        'price_toman',
        'status',
        'starts_at',
        'ends_at',
        'gateway_authority',
        'gateway_ref_id',
        'payment_source',
        'paid_at',
    ];

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function price(): Money
    {
        return Money::toman($this->price_toman);
    }

    /** سهم پرداخت‌نشده این دوره در لحظه‌ای مشخص — مبنای بازگشت وجه نسبت‌به‌مدت. */
    public function unusedValue(?Carbon $at = null): Money
    {
        if ($this->status !== PeriodStatus::Paid) {
            return Money::zero();
        }

        return UnusedShare::of($this->price_toman, $this->starts_at, $this->ends_at, $at ?? Carbon::now());
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'billing_cycle' => BillingCycle::class,
            'price_toman' => 'integer',
            'status' => PeriodStatus::class,
            'payment_source' => PaymentSource::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }
}
