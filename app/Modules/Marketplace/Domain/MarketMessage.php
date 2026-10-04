<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain;

use App\Modules\Marketplace\Domain\Enums\MessageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * پیام گفت‌وگوی یک پیشنهاد، پیش و پس از قرارداد (DEC-80).
 *
 * @property int $id
 * @property string $uuid
 * @property int $bid_id
 * @property int $sender_user_id
 * @property string $body
 * @property MessageStatus $status
 * @property list<string>|null $flags
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read MarketBid $bid
 */
final class MarketMessage extends Model
{
    protected $fillable = [
        'uuid',
        'bid_id',
        'sender_user_id',
        'body',
        'status',
        'flags',
    ];

    /** @return BelongsTo<MarketBid, $this> */
    public function bid(): BelongsTo
    {
        return $this->belongsTo(MarketBid::class, 'bid_id');
    }

    /** فرستنده همه پیام‌هایش را می‌بیند؛ طرف مقابل فقط رسیده‌ها را. */
    public function isVisibleTo(int $userId): bool
    {
        return $this->sender_user_id === $userId || $this->status === MessageStatus::Delivered;
    }

    protected function casts(): array
    {
        return [
            'status' => MessageStatus::class,
            'flags' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }
}
