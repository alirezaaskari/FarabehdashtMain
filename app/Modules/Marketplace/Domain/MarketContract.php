<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain;

use App\Modules\Marketplace\Domain\Enums\ContractStatus;
use App\Modules\Marketplace\Domain\Enums\MilestoneStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * قرارداد کارفرما و مجری روی یک پروژه (بخش ۲۱-۴): مرحله‌های پیشنهاد
 * پذیرفته‌شده، منجمد، با نرخ کمیسیون روز پذیرش.
 *
 * @property int $id
 * @property string $uuid
 * @property int $project_id
 * @property int $bid_id
 * @property int $client_user_id
 * @property int $provider_user_id
 * @property int $total_toman
 * @property int $commission_bp
 * @property ContractStatus $status
 * @property Carbon $pay_by
 * @property Carbon|null $completed_at
 * @property Carbon|null $lapsed_at
 * @property Carbon|null $cancelled_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read MarketProject $project
 * @property-read MarketBid $bid
 * @property-read Collection<int, MarketMilestone> $milestones
 */
final class MarketContract extends Model
{
    protected $fillable = [
        'uuid',
        'project_id',
        'bid_id',
        'client_user_id',
        'provider_user_id',
        'total_toman',
        'commission_bp',
        'status',
        'pay_by',
    ];

    /** @return BelongsTo<MarketProject, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(MarketProject::class, 'project_id');
    }

    /** @return BelongsTo<MarketBid, $this> */
    public function bid(): BelongsTo
    {
        return $this->belongsTo(MarketBid::class, 'bid_id');
    }

    /** @return HasMany<MarketMilestone, $this> */
    public function milestones(): HasMany
    {
        return $this->hasMany(MarketMilestone::class, 'contract_id')->orderBy('position');
    }

    public function total(): Money
    {
        return Money::toman($this->total_toman);
    }

    public function involves(int $userId): bool
    {
        return $userId === $this->client_user_id || $userId === $this->provider_user_id;
    }

    /**
     * مرحله‌ای که حالا نوبت پرداختش است: اولین مرحله پرداخت‌نشده، به شرط
     * آن‌که مرحله قبلی آزاد شده باشد (مرحله بعد پس از آزادسازی قبلی).
     */
    public function payable(): ?MarketMilestone
    {
        if (! $this->status->isOpen()) {
            return null;
        }

        foreach ($this->milestones as $milestone) {
            if ($milestone->status === MilestoneStatus::Released) {
                continue;
            }

            return $milestone->status === MilestoneStatus::Unpaid ? $milestone : null;
        }

        return null;
    }

    /** هیچ پولی از این قرارداد در امانت نیست. */
    public function holdsNothing(): bool
    {
        return $this->milestones->every(static fn (MarketMilestone $milestone): bool => ! $milestone->status->isHeld());
    }

    public function releasedTotal(): Money
    {
        return Money::toman((int) $this->milestones->where('status', MilestoneStatus::Released)->sum('amount_toman'));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ContractStatus::class,
            'pay_by' => 'datetime',
            'completed_at' => 'datetime',
            'lapsed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
