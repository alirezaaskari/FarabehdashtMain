<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * مدرکی که مشاور برای بررسی مدیر فرستاده. خصوصی است و فقط خود مشاور و
 * مدیر محتوا دانلودش می‌کنند؛ روی صفحه عمومی هیچ نشانی از آن نیست (DEC-50).
 *
 * @property int $id
 * @property string $uuid
 * @property int $profile_id
 * @property string $path
 * @property string $original_name
 * @property int $size_bytes
 * @property Carbon $created_at
 * @property-read ConsultantProfile $profile
 */
final class ConsultantDocument extends Model
{
    protected $fillable = [
        'uuid',
        'profile_id',
        'path',
        'original_name',
        'size_bytes',
    ];

    /** @return BelongsTo<ConsultantProfile, $this> */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(ConsultantProfile::class, 'profile_id');
    }
}
