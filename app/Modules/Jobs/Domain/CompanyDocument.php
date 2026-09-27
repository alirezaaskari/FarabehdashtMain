<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * مدرک ثبت شرکت یا معرفی‌نامه برای سنجش مدیر (DEC-65). خصوصی است و فقط
 * خود کارفرما و مدیر کاریابی دانلودش می‌کنند.
 *
 * @property int $id
 * @property string $uuid
 * @property int $company_id
 * @property string $path
 * @property string $original_name
 * @property int $size_bytes
 * @property Carbon $created_at
 * @property-read Company $company
 */
final class CompanyDocument extends Model
{
    protected $fillable = [
        'uuid',
        'company_id',
        'path',
        'original_name',
        'size_bytes',
    ];

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
