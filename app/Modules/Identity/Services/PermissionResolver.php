<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Models\User;
use App\Modules\Identity\Domain\UserProfile;

/**
 * مجوزهای یک کاربر.
 *
 * قاعده محصول: مجوزها جمع‌شونده‌اند — اتحاد مجوزهای پایه با مجوزهای همه
 * پروفایل‌های فعال. پروفایل در انتظار تأیید، معلق یا غیرفعال هیچ مجوزی اضافه
 * نمی‌کند، ولی داده‌هایش هم دست‌نخورده می‌ماند.
 *
 * نگاشت مجوزها در config/identity.php است تا افزودن مجوز تازه به یک نقش،
 * تغییر کد لازم نداشته باشد.
 */
final readonly class PermissionResolver
{
    /**
     * @param  list<string>  $base
     * @param  array<string, list<string>>  $byProfile
     * @param  array<string, string>  $labels
     */
    public function __construct(
        private array $base,
        private array $byProfile,
        private array $labels = [],
    ) {}

    /** @return list<string> */
    public function for(User $user): array
    {
        $permissions = $this->base;

        foreach ($user->activeProfiles() as $profile) {
            /** @var UserProfile $profile */
            $permissions = array_merge($permissions, $this->byProfile[$profile->type->value] ?? []);
        }

        $unique = array_values(array_unique($permissions));
        sort($unique);

        return $unique;
    }

    /** برچسب فارسی مجوز؛ اگر برچسبی ثبت نشده باشد، خود کلید. */
    public function label(string $permission): string
    {
        return $this->labels[$permission] ?? $permission;
    }

    public function allows(User $user, string $permission): bool
    {
        return in_array($permission, $this->for($user), strict: true);
    }

    /** @return list<string> */
    public function known(): array
    {
        $all = $this->base;

        foreach ($this->byProfile as $permissions) {
            $all = array_merge($all, $permissions);
        }

        $unique = array_values(array_unique($all));
        sort($unique);

        return $unique;
    }
}
