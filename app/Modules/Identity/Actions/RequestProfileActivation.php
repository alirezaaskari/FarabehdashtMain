<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Models\User;
use App\Modules\Identity\Domain\Enums\ProfileStatus;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Identity\Domain\UserProfile;

/**
 * درخواست فعال‌سازی یک نقش تجاری.
 *
 * قاعده محصول: هیچ پروفایل تجاری بدون تأیید مدیر فعال نمی‌شود، پس وضعیت
 * اولیه همیشه «در انتظار تأیید» است — حتی اگر قبلاً فعال بوده و کاربر
 * خودش غیرفعالش کرده باشد.
 */
final readonly class RequestProfileActivation
{
    /** @param array<string, mixed> $meta */
    public function handle(User $user, ProfileType $type, array $meta = []): UserProfile
    {
        $profile = UserProfile::query()->firstOrNew([
            'user_id' => $user->getKey(),
            'type' => $type->value,
        ]);

        $profile->fill([
            'status' => ProfileStatus::Pending->value,
            'requested_at' => now(),
            'approved_at' => null,
            'approved_by' => null,
            'rejection_note' => null,
            'meta' => array_merge($profile->meta ?? [], $meta),
        ])->save();

        $user->unsetRelation('profiles');

        return $profile;
    }
}
