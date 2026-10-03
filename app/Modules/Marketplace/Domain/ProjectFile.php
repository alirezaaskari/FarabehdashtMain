<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * پیوست خصوصی پروژه روی دیسک local؛ هرگز روی صفحه عمومی.
 *
 * @property int $id
 * @property string $uuid
 * @property int $project_id
 * @property string $path
 * @property string $original_name
 * @property int $size_bytes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read MarketProject $project
 */
final class ProjectFile extends Model
{
    protected $table = 'market_project_files';

    protected $fillable = [
        'uuid',
        'project_id',
        'path',
        'original_name',
        'size_bytes',
    ];

    /** @return BelongsTo<MarketProject, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(MarketProject::class, 'project_id');
    }
}
