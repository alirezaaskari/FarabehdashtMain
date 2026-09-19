<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Models\User;
use App\Modules\Identity\Domain\Enums\ProfileStatus;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Identity\Domain\UserProfile;
use App\Modules\Identity\Events\ProfileDeactivated;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * غیرفعال‌کردن نقش توسط خود کاربر.
 *
 * قاعده محصول: هیچ داده‌ای حذف نمی‌شود. فقط دسترسی و نمایش قطع می‌شود و
 * میزکار دیگر بخش مربوط به این نقش را نشان نمی‌دهد.
 */
final readonly class DeactivateProfile
{
    public function __construct(private Dispatcher $events) {}

    public function handle(User $user, ProfileType $type): ?UserProfile
    {
        $profile = $user->profileFor($type);

        if (! $profile instanceof UserProfile) {
            return null;
        }

        $previousStatus = $profile->status;

        $profile->forceFill(['status' => ProfileStatus::Disabled->value])->save();

        $user->unsetRelation('profiles');

        $this->events->dispatch(new ProfileDeactivated($profile, $previousStatus));

        return $profile;
    }
}
