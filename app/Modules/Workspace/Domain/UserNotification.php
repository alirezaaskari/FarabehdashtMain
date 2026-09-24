<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Domain;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

/**
 * یک اعلان درون‌سایتی.
 *
 * نام کلاس `UserNotification` است نه `Notification`، تا با اعلان‌های
 * Laravel و Filament اشتباه گرفته نشود.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string $kind
 * @property string $title
 * @property string|null $body
 * @property string|null $route_name
 * @property array<string, string|int>|null $route_parameters
 * @property Carbon|null $read_at
 * @property Carbon $created_at
 */
final class UserNotification extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'workspace_notifications';

    protected $fillable = [
        'uuid',
        'user_id',
        'kind',
        'title',
        'body',
        'route_name',
        'route_parameters',
        'read_at',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param  Builder<$this>  $query */
    public function scopeUnread(Builder $query): void
    {
        $query->whereNull('read_at');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * نشانی مقصد، یا null وقتی ماژول صاحب مسیر دیگر نیست یا پارامترهایش
     * دیگر معتبر نیستند.
     */
    public function url(): ?string
    {
        if ($this->route_name === null || ! Route::has($this->route_name)) {
            return null;
        }

        return route($this->route_name, $this->route_parameters ?? []);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'route_parameters' => 'array',
            'read_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
