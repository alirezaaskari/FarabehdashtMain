<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Domain;

use App\Modules\Monetization\Domain\Enums\BillingCycle;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * یک پلن اشتراک: ماهانه یا سالانه، با قیمت تومانی.
 *
 * قیمت این‌جا «قیمت امروز» است، نه قیمت تاریخی: هر دوره پرداخت، قیمت را روی
 * خودش Snapshot می‌کند (`SubscriptionPeriod`)، پس مدیر می‌تواند قیمت را عوض
 * کند بدون اینکه صورتحساب‌های گذشته تکان بخورند.
 *
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property BillingCycle $billing_cycle
 * @property int $price_toman
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class Plan extends Model
{
    protected $table = 'subscription_plans';

    protected $fillable = [
        'slug',
        'title',
        'billing_cycle',
        'price_toman',
        'is_active',
        'sort_order',
    ];

    public function price(): Money
    {
        return Money::toman($this->price_toman);
    }

    /** @param  Builder<$this>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'billing_cycle' => BillingCycle::class,
            'price_toman' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
