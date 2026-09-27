<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * پیام درون سایت درباره یک درخواست؛ فقط کارجو و کارفرمای همان آگهی.
 *
 * @property int $id
 * @property int $application_id
 * @property int $user_id
 * @property string $body
 * @property Carbon $created_at
 * @property-read JobApplication $application
 */
final class ApplicationMessage extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'job_application_messages';

    protected $fillable = [
        'application_id',
        'user_id',
        'body',
    ];

    /** @return BelongsTo<JobApplication, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'application_id');
    }
}
