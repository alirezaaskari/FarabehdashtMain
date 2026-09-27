<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * آگهی‌ای که به یک کاربر هشدار داده شد؛ هر آگهی برای هر کاربر یک بار.
 *
 * @property int $id
 * @property int $user_id
 * @property int $posting_id
 * @property Carbon|null $digested_at
 * @property Carbon $created_at
 * @property-read JobPosting $posting
 */
final class JobAlertMatch extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'posting_id',
        'digested_at',
    ];

    /** @return BelongsTo<JobPosting, $this> */
    public function posting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'posting_id');
    }

    protected function casts(): array
    {
        return ['digested_at' => 'datetime'];
    }
}
