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
 * یک دوره پرداخت تیم، با تعداد صندلی و قیمت واحد Snapshot‌شده.
 *
 * @property int $id
 * @property string $uuid
 * @property int $team_id
 * @property int $seats
 * @property BillingCycle $billing_cycle
 * @property int $unit_price_toman
 * @property int $price_toman
 * @property PeriodStatus $status
 * @property PaymentSource|null $payment_source
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property string|null $gateway_authority
 * @property string|null $gateway_ref_id
 * @property Carbon|null $paid_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Team $team
 */
final class TeamPeriod extends Model
{
    protected $fillable = [
        'uuid',
        'team_id',
        'seats',
        'billing_cycle',
        'unit_price_toman',
        'price_toman',
        'status',
    ];

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function price(): Money
    {
        return Money::toman($this->price_toman);
    }

    /** سهم روزهای مانده، مبنای بازگشت وجه تیم. */
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
            'seats' => 'integer',
            'billing_cycle' => BillingCycle::class,
            'unit_price_toman' => 'integer',
            'price_toman' => 'integer',
            'status' => PeriodStatus::class,
            'payment_source' => PaymentSource::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }
}
