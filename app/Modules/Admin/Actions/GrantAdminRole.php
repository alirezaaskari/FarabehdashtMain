<?php

declare(strict_types=1);

namespace App\Modules\Admin\Actions;

use App\Models\User;
use App\Modules\Admin\Domain\AdminRoleAssignment;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Admin\Events\AdminRoleGranted;
use App\Modules\Admin\Services\AdminAccess;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * اعطای نقش مدیریتی.
 *
 * `$grantedBy` وقتی null است که دستور کنسول مدیر اول را می‌سازد — تنها حالتی
 * که اعطاکننده‌ای وجود ندارد. در همه مسیرهای دیگر، مدیر ارشدِ اعطاکننده ثبت
 * می‌شود و بدون او این کار انجام نمی‌گیرد.
 */
final readonly class GrantAdminRole
{
    public function __construct(
        private Dispatcher $events,
        private AdminAccess $access,
    ) {}

    public function handle(User $user, AdminRole $role, ?User $grantedBy = null, ?string $note = null): AdminRoleAssignment
    {
        $assignment = AdminRoleAssignment::query()->firstOrNew([
            'user_id' => $user->getKey(),
            'role' => $role->value,
        ]);

        $alreadyHeld = $assignment->exists;

        $assignment->fill([
            'granted_by' => $grantedBy?->getKey(),
            'note' => $note,
        ])->save();

        $this->access->forget($user);

        // اعطای دوباره نقشی که از قبل هست، رویداد تازه نمی‌سازد؛ دفتر رویداد
        // باید تغییر را نشان بدهد، نه تکرار را.
        if (! $alreadyHeld) {
            $this->events->dispatch(new AdminRoleGranted(
                $user,
                $role,
                $grantedBy === null ? null : (int) $grantedBy->getKey(),
                $note,
            ));
        }

        return $assignment;
    }
}
