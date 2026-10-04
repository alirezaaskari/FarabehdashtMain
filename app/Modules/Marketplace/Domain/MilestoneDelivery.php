<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * یک بار تحویل مرحله با توضیح و فایل خصوصی؛ درخواست اصلاح کارفرما روی همین ردیف.
 *
 * @property int $id
 * @property string $uuid
 * @property int $milestone_id
 * @property string $note
 * @property string|null $revision_note
 * @property Carbon|null $revision_requested_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read MarketMilestone $milestone
 * @property-read Collection<int, DeliveryFile> $files
 */
final class MilestoneDelivery extends Model
{
    protected $table = 'market_deliveries';

    protected $fillable = [
        'uuid',
        'milestone_id',
        'note',
    ];

    /** @return BelongsTo<MarketMilestone, $this> */
    public function milestone(): BelongsTo
    {
        return $this->belongsTo(MarketMilestone::class, 'milestone_id');
    }

    /** @return HasMany<DeliveryFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(DeliveryFile::class, 'delivery_id')->oldest('id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['revision_requested_at' => 'datetime'];
    }
}
