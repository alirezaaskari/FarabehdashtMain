<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Domain;

use App\Modules\Chemicals\Domain\Enums\FactKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * یک نکته درباره ماده: مسیر مواجهه، علامت، یا اقدام حفاظتی.
 *
 * @property int $id
 * @property int $substance_id
 * @property FactKind $kind
 * @property int $position
 * @property string $text
 */
final class SubstanceFact extends Model
{
    protected $fillable = ['substance_id', 'kind', 'position', 'text'];

    /** @return BelongsTo<Substance, $this> */
    public function substance(): BelongsTo
    {
        return $this->belongsTo(Substance::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['kind' => FactKind::class, 'position' => 'integer'];
    }
}
