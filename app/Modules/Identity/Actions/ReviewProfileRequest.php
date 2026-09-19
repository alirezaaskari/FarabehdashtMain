<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Models\User;
use App\Modules\Identity\Domain\Enums\ProfileStatus;
use App\Modules\Identity\Domain\UserProfile;

/**
 * تأیید یا رد درخواست پروفایل توسط مدیر.
 *
 * رد کردن همیشه یادداشت دارد؛ کاربر باید بداند چرا و چه چیزی را اصلاح کند.
 */
final readonly class ReviewProfileRequest
{
    public function approve(UserProfile $profile, User $admin): UserProfile
    {
        $profile->forceFill([
            'status' => ProfileStatus::Active->value,
            'approved_at' => now(),
            'approved_by' => $admin->getKey(),
            'rejection_note' => null,
        ])->save();

        return $profile;
    }

    public function reject(UserProfile $profile, User $admin, string $note): UserProfile
    {
        $profile->forceFill([
            'status' => ProfileStatus::Disabled->value,
            'approved_at' => null,
            'approved_by' => $admin->getKey(),
            'rejection_note' => $note,
        ])->save();

        return $profile;
    }
}
