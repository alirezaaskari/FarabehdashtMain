<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain;

use App\Modules\Marketplace\Domain\Enums\BidStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * پیشنهاد یک مجری روی یک پروژه (بخش ۲۱-۳)؛ هر مجری روی هر پروژه یکی.
 *
 * @property int $id
 * @property string $uuid
 * @property int $project_id
 * @property int $provider_user_id
 * @property string $cover
 * @property list<array{title: string, amount_toman: int, days: int}> $milestones
 * @property int $total_toman
 * @property int $total_days
 * @property BidStatus $status
 * @property Carbon|null $withdrawn_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read MarketProject $project
 * @property-read Collection<int, MarketMessage> $messages
 * @property-read MarketContract|null $contract
 */
final class MarketBid extends Model
{
    protected $fillable = [
        'uuid',
        'project_id',
        'provider_user_id',
        'cover',
        'milestones',
        'total_toman',
        'total_days',
        'status',
    ];

    /** @return BelongsTo<MarketProject, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(MarketProject::class, 'project_id');
    }

    /** @return HasOne<MarketContract, $this> */
    public function contract(): HasOne
    {
        return $this->hasOne(MarketContract::class, 'bid_id');
    }

    /** @return HasMany<MarketMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(MarketMessage::class, 'bid_id');
    }

    public function total(): Money
    {
        return Money::toman($this->total_toman);
    }

    /** کارفرما یا مجری همین پیشنهاد. */
    public function involves(int $userId): bool
    {
        return $userId === $this->provider_user_id || $userId === $this->project->client_user_id;
    }

    public function counterpartOf(int $userId): int
    {
        return $userId === $this->provider_user_id ? $this->project->client_user_id : $this->provider_user_id;
    }

    /** @return list<string> */
    public function coverParagraphs(): array
    {
        return array_values(array_filter(array_map(trim(...), preg_split('/\R{2,}/u', $this->cover) ?: [])));
    }

    protected function casts(): array
    {
        return [
            'milestones' => 'array',
            'status' => BidStatus::class,
            'withdrawn_at' => 'datetime',
        ];
    }
}
