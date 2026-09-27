<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Domain;

use App\Modules\Jobs\Domain\Enums\AccessKind;
use App\Modules\Jobs\Domain\Enums\AccessSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * یک بار دیده‌شدن شماره یا رزومه کارجو به دست یک کارفرما (DEC-68). فقط
 * افزوده می‌شود؛ کارجو فهرست خودش را در میزکار می‌بیند.
 *
 * @property int $id
 * @property int $jobseeker_id
 * @property int|null $viewer_id
 * @property int|null $company_id
 * @property int|null $application_id
 * @property AccessSource $source
 * @property AccessKind $kind
 * @property Carbon $created_at
 * @property-read Company|null $company
 */
final class ResumeAccessLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'jobseeker_id',
        'viewer_id',
        'company_id',
        'application_id',
        'source',
        'kind',
    ];

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected function casts(): array
    {
        return [
            'source' => AccessSource::class,
            'kind' => AccessKind::class,
        ];
    }
}
