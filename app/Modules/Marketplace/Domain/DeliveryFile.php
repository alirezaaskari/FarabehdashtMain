<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * فایل تحویل مرحله روی دیسک local؛ فقط دو طرف قرارداد و مدیر.
 *
 * @property int $id
 * @property string $uuid
 * @property int $delivery_id
 * @property string $path
 * @property string $original_name
 * @property int $size_bytes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read MilestoneDelivery $delivery
 */
final class DeliveryFile extends Model
{
    protected $table = 'market_delivery_files';

    protected $fillable = [
        'uuid',
        'delivery_id',
        'path',
        'original_name',
        'size_bytes',
    ];

    /** @return BelongsTo<MilestoneDelivery, $this> */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(MilestoneDelivery::class, 'delivery_id');
    }
}
