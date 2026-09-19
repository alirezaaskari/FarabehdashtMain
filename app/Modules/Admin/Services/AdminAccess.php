<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Models\User;
use App\Modules\Admin\Domain\AdminRoleAssignment;
use App\Modules\Admin\Domain\Enums\AdminRole;

/**
 * توانایی‌های مدیریتی یک کاربر.
 *
 * دو سامانه مجوز عمداً جدا هستند: مجوزهای ماژول Identity درباره کارهای تجاری
 * کاربر است (فروش، آگهی، مشاوره) و این‌یکی درباره اداره سایت. قاطی‌کردنشان یعنی
 * یک مسیر عمومی درخواست پروفایل، روزی به دسترسی مالی می‌رسد.
 *
 * همه توانایی‌ها پیشوند `admin.` دارند تا در کد هیچ‌وقت با مجوز کاربری اشتباه
 * گرفته نشوند.
 */
final class AdminAccess
{
    /** @var array<int, list<string>> */
    private array $cache = [];

    /** @return list<AdminRole> */
    public function rolesOf(User $user): array
    {
        $roles = AdminRoleAssignment::query()
            ->forUser((int) $user->getKey())
            ->pluck('role')
            ->all();

        return array_values(array_map(
            static fn (AdminRole|string $role): AdminRole => $role instanceof AdminRole
                ? $role
                : AdminRole::from($role),
            $roles,
        ));
    }

    /**
     * اتحاد توانایی‌های همه نقش‌های مدیریتی کاربر.
     *
     * @return list<string>
     */
    public function abilitiesOf(User $user): array
    {
        $key = (int) $user->getKey();

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $abilities = [];

        foreach ($this->rolesOf($user) as $role) {
            $abilities = [...$abilities, ...$role->abilities()];
        }

        $unique = array_values(array_unique($abilities));
        sort($unique);

        return $this->cache[$key] = $unique;
    }

    public function allows(User $user, string $ability): bool
    {
        return in_array($ability, $this->abilitiesOf($user), strict: true);
    }

    public function isAdmin(User $user): bool
    {
        return $this->allows($user, 'admin.panel.access');
    }

    public function hasRole(User $user, AdminRole $role): bool
    {
        return in_array($role, $this->rolesOf($user), strict: true);
    }

    /** حافظه موقت را پاک می‌کند؛ پس از اعطا یا گرفتن نقش لازم است. */
    public function forget(User $user): void
    {
        unset($this->cache[(int) $user->getKey()]);
    }
}
