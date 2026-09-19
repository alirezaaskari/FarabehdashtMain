<?php

declare(strict_types=1);

namespace App\Modules\Admin\Domain;

use App\Models\User;
use App\Modules\Admin\Domain\Enums\AdminRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * اعطای یک نقش مدیریتی به یک کاربر.
 *
 * @property int $id
 * @property int $user_id
 * @property AdminRole $role
 * @property int|null $granted_by
 * @property string|null $note
 * @property Carbon $created_at
 */
final class AdminRoleAssignment extends Model
{
    protected $table = 'admin_roles';

    protected $fillable = ['user_id', 'role', 'granted_by', 'note'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /** @param  Builder<$this>  $query */
    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['role' => AdminRole::class];
    }
}
