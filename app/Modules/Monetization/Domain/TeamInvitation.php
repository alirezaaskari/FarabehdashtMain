<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Domain;

use App\Modules\Monetization\Domain\Enums\InvitationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * دعوت یک شماره موبایل به تیم. شماره را خود صاحب تیم نوشته است؛ دفتر رویداد
 * فقط شناسه دعوت را می‌گیرد.
 *
 * @property int $id
 * @property string $uuid
 * @property int $team_id
 * @property string $mobile
 * @property int $invited_by
 * @property InvitationStatus $status
 * @property Carbon|null $responded_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Team $team
 */
final class TeamInvitation extends Model
{
    protected $fillable = ['uuid', 'team_id', 'mobile', 'invited_by', 'status'];

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @param  Builder<$this>  $query */
    public function scopePending(Builder $query): void
    {
        $query->where('status', InvitationStatus::Pending);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => InvitationStatus::class,
            'responded_at' => 'datetime',
        ];
    }
}
