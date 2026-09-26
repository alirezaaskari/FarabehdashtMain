<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $bundle_id
 * @property string $kind
 * @property string $ref
 * @property int $sort
 */
final class BundleItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['bundle_id', 'kind', 'ref', 'sort'];

    /** @return BelongsTo<Bundle, $this> */
    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    public function key(): string
    {
        return $this->kind.':'.$this->ref;
    }
}
