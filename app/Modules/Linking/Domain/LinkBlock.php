<?php

declare(strict_types=1);

namespace App\Modules\Linking\Domain;

use App\Modules\Linking\Services\PhraseMatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * قاعده مسدودسازی مدیر (DEC-32).
 *
 * هر ستون پرشده یک شرط است و همه شرط‌ها باید با هم برقرار باشند:
 * فقط عبارت → آن عبارت هیچ‌جا پیوند نمی‌خورد · فقط مقصد → هیچ متنی به آن صفحه
 * پیوند نمی‌دهد · مبدأ و مقصد → فقط همان جفت.
 *
 * @property int $id
 * @property string|null $phrase
 * @property string|null $source_key
 * @property string|null $target_key
 * @property int|null $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class LinkBlock extends Model
{
    protected $fillable = ['phrase', 'source_key', 'target_key', 'created_by'];

    public function blocks(string $sourceKey, string $targetKey, string $phrase): bool
    {
        return ($this->phrase === null || PhraseMatcher::normalise($this->phrase) === $phrase)
            && ($this->source_key === null || $this->source_key === $sourceKey)
            && ($this->target_key === null || $this->target_key === $targetKey);
    }
}
