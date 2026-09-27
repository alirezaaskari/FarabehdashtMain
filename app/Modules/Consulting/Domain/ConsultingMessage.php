<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * یک پیام در گفت‌وگوی درخواست خدمت.
 *
 * @property int $id
 * @property int $order_id
 * @property int $user_id
 * @property string $body
 * @property Carbon $created_at
 * @property-read ConsultingOrder $order
 */
final class ConsultingMessage extends Model
{
    protected $fillable = ['order_id', 'user_id', 'body'];

    /** @return BelongsTo<ConsultingOrder, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(ConsultingOrder::class, 'order_id');
    }
}
