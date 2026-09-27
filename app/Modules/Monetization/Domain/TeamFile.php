<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Domain;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * فایلی که یک عضو در کتابخانه تیم گذاشته (DEC-62: فقط فایل خود اعضا).
 *
 * @property int $id
 * @property string $uuid
 * @property int $team_id
 * @property int $uploader_id
 * @property string $title
 * @property string $original_name
 * @property string $path
 * @property string $mime
 * @property int $size_bytes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Team $team
 * @property-read User $uploader
 */
final class TeamFile extends Model
{
    protected $fillable = ['uuid', 'team_id', 'uploader_id', 'title', 'original_name', 'path', 'mime', 'size_bytes'];

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['size_bytes' => 'integer'];
    }
}
