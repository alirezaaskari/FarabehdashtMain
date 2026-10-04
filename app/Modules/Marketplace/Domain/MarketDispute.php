<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * اعتراض یک طرف روی یک مرحله و رأی مدیر (بخش ۲۱-۵).
 *
 * @property int $id
 * @property string $uuid
 * @property int $milestone_id
 * @property int $opened_by
 * @property string $reason
 * @property int|null $to_client_toman
 * @property string|null $note
 * @property int|null $resolved_by
 * @property Carbon|null $resolved_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read MarketMilestone $milestone
 */
final class MarketDispute extends Model
{
    protected $fillable = [
        'uuid',
        'milestone_id',
        'opened_by',
        'reason',
    ];

    /** @return BelongsTo<MarketMilestone, $this> */
    public function milestone(): BelongsTo
    {
        return $this->belongsTo(MarketMilestone::class, 'milestone_id');
    }

    public function toClient(): ?Money
    {
        return $this->to_client_toman === null ? null : Money::toman($this->to_client_toman);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }
}
