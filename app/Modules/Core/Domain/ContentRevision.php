<?php

declare(strict_types=1);

namespace App\Modules\Core\Domain;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * یک نسخه پیشین از یک محتوا.
 *
 * @property int $id
 * @property string $revisable_type
 * @property int $revisable_id
 * @property int $version
 * @property array<string, mixed> $snapshot
 * @property string|null $reason
 * @property int|null $author_id
 * @property Carbon $created_at
 */
final class ContentRevision extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'revisable_type',
        'revisable_id',
        'version',
        'snapshot',
        'reason',
        'author_id',
    ];

    /** @return MorphTo<Model, $this> */
    public function revisable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'version' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
