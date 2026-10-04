<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain;

use App\Modules\Marketplace\Domain\Enums\MilestoneStatus;
use App\Support\Money;
use App\Support\Payments\PaymentSource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * یک مرحله قرارداد با امانت جدای خودش (بخش ۲۱-۱ و ۲۱-۴).
 *
 * @property int $id
 * @property string $uuid
 * @property int $contract_id
 * @property int $position
 * @property string $title
 * @property int $amount_toman
 * @property int $days
 * @property MilestoneStatus $status
 * @property int|null $commission_toman
 * @property string|null $escrow_uuid
 * @property PaymentSource|null $payment_source
 * @property string|null $gateway_authority
 * @property string|null $gateway_ref_id
 * @property Carbon|null $funded_at
 * @property Carbon|null $due_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $release_at
 * @property int $revisions
 * @property Carbon|null $released_at
 * @property bool $auto_released
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read MarketContract $contract
 * @property-read Collection<int, MilestoneDelivery> $deliveries
 */
final class MarketMilestone extends Model
{
    protected $fillable = [
        'uuid',
        'contract_id',
        'position',
        'title',
        'amount_toman',
        'days',
        'status',
    ];

    /** @return BelongsTo<MarketContract, $this> */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(MarketContract::class, 'contract_id');
    }

    /** @return HasMany<MilestoneDelivery, $this> */
    public function deliveries(): HasMany
    {
        return $this->hasMany(MilestoneDelivery::class, 'milestone_id')->oldest('id');
    }

    public function amount(): Money
    {
        return Money::toman($this->amount_toman);
    }

    /** سهم مجری پس از کمیسیون، با نرخ ثبت‌شده روی قرارداد. */
    public function providerShare(int $commissionBp): Money
    {
        return $this->amount()->minus($this->commissionAt($commissionBp));
    }

    public function commissionAt(int $commissionBp): Money
    {
        return $this->commission_toman !== null ? Money::toman($this->commission_toman) : $this->amount()->percentage($commissionBp / 100);
    }

    public function escrowKey(): string
    {
        return 'marketplace.milestone:'.$this->uuid;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => MilestoneStatus::class,
            'payment_source' => PaymentSource::class,
            'funded_at' => 'datetime',
            'due_at' => 'datetime',
            'delivered_at' => 'datetime',
            'release_at' => 'datetime',
            'released_at' => 'datetime',
            'auto_released' => 'boolean',
        ];
    }
}
