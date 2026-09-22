<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * یک نام دیگر برای همان ماده.
 *
 * @property int $id
 * @property int $substance_id
 * @property string $name
 */
final class SubstanceSynonym extends Model
{
    protected $fillable = ['substance_id', 'name'];

    /** @return BelongsTo<Substance, $this> */
    public function substance(): BelongsTo
    {
        return $this->belongsTo(Substance::class);
    }
}
