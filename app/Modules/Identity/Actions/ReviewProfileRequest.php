<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Models\User;
use App\Modules\Identity\Domain\Enums\ProfileStatus;
use App\Modules\Identity\Domain\UserProfile;
use App\Modules\Identity\Events\ProfileApproved;
use App\Modules\Identity\Events\ProfileRejected;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * تأیید یا رد درخواست پروفایل توسط مدیر.
 *
 * رد کردن همیشه یادداشت دارد؛ کاربر باید بداند چرا و چه چیزی را اصلاح کند.
 */
final readonly class ReviewProfileRequest
{
    public function __construct(private Dispatcher $events) {}

    public function approve(UserProfile $profile, User $admin): UserProfile
    {
        $previousStatus = $profile->status;

        $profile->forceFill([
            'status' => ProfileStatus::Active->value,
            'approved_at' => now(),
            'approved_by' => $admin->getKey(),
            'rejection_note' => null,
        ])->save();

        $this->events->dispatch(new ProfileApproved($profile, $previousStatus, (int) $admin->getKey()));

        return $profile;
    }

    public function reject(UserProfile $profile, User $admin, string $note): UserProfile
    {
        $previousStatus = $profile->status;

        $profile->forceFill([
            'status' => ProfileStatus::Disabled->value,
            'approved_at' => null,
            'approved_by' => $admin->getKey(),
            'rejection_note' => $note,
        ])->save();

        $this->events->dispatch(new ProfileRejected($profile, $previousStatus, (int) $admin->getKey(), $note));

        return $profile;
    }
}
