<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * دعوت مستقیم کارفرما از یک مشاور یا آزمایشگاه (DEC-89). دعوت‌شده پروژه
 * خصوصی را هم می‌بیند و رویش پیشنهاد می‌دهد (DEC-90).
 *
 * @property int $id
 * @property int $project_id
 * @property int $provider_user_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read MarketProject $project
 */
final class MarketInvite extends Model
{
    protected $fillable = ['project_id', 'provider_user_id'];

    /** @return BelongsTo<MarketProject, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(MarketProject::class, 'project_id');
    }
}
