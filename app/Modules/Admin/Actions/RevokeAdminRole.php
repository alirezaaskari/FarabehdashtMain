<?php

declare(strict_types=1);

namespace App\Modules\Admin\Actions;

use App\Models\User;
use App\Modules\Admin\Domain\AdminRoleAssignment;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Admin\Events\AdminRoleRevoked;
use App\Modules\Admin\Services\AdminAccess;
use Illuminate\Contracts\Events\Dispatcher;
use RuntimeException;

/**
 * پس‌گرفتن نقش مدیریتی.
 *
 * قاعده: آخرین مدیر ارشد پس گرفته نمی‌شود. سایتی بدون مدیر ارشد، سایتی است که
 * هیچ‌کس دیگر نمی‌تواند نقشی اعطا کند — بن‌بست کامل.
 */
final readonly class RevokeAdminRole
{
    public function __construct(
        private Dispatcher $events,
        private AdminAccess $access,
    ) {}

    /**
     * @throws RuntimeException اگر بخواهد آخرین مدیر ارشد را بردارد
     */
    public function handle(User $user, AdminRole $role, ?User $revokedBy = null): bool
    {
        $assignment = AdminRoleAssignment::query()
            ->forUser((int) $user->getKey())
            ->where('role', $role->value)
            ->first();

        if (! $assignment instanceof AdminRoleAssignment) {
            return false;
        }

        if ($role === AdminRole::Super && $this->superAdminCount() <= 1) {
            throw new RuntimeException('آخرین مدیر ارشد را نمی‌توان برداشت؛ سایت بدون مدیر ارشد می‌ماند.');
        }

        $assignment->delete();

        $this->access->forget($user);

        $this->events->dispatch(new AdminRoleRevoked(
            $user,
            $role,
            $revokedBy === null ? null : (int) $revokedBy->getKey(),
        ));

        return true;
    }

    private function superAdminCount(): int
    {
        return AdminRoleAssignment::query()->where('role', AdminRole::Super->value)->count();
    }
}
