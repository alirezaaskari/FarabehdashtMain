<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Domain;

use App\Modules\Monetization\Domain\Enums\BillingCycle;
use App\Modules\Monetization\Domain\Enums\PeriodStatus;
use App\Support\Money;
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

    /**
     * سهم پرداخت‌نشده این دوره در لحظه‌ای مشخص — مبنای بازگشت وجه نسبت‌به‌مدت.
     *
     * محاسبه روی روز است نه ثانیه: کاربر «هفده روز مانده» را می‌فهمد و
     * می‌تواند خودش حساب کند؛ عددی که از ثانیه درآمده باشد قابل بررسی نیست.
     */
    public function unusedValue(?Carbon $at = null): Money
    {
        $at ??= Carbon::now();

        if ($this->status !== PeriodStatus::Paid || $this->starts_at === null || $this->ends_at === null) {
            return Money::zero();
        }

        // اختلاف روی مهر زمانی حساب می‌شود و نه با diffInDays: آن متد اعشار
        // برمی‌گرداند و «روز» این‌جا باید عدد صحیح باشد تا مبلغ بازگشتی
        // قابل بررسی دستی بماند.
        $total = intdiv($this->ends_at->getTimestamp() - $this->starts_at->getTimestamp(), 86400);
        $left = intdiv($this->ends_at->getTimestamp() - $at->getTimestamp(), 86400);

        if ($total <= 0 || $left <= 0) {
            return Money::zero();
        }

        return Money::toman(intdiv($this->price_toman * min($left, $total), $total));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'billing_cycle' => BillingCycle::class,
            'price_toman' => 'integer',
            'status' => PeriodStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }
}
