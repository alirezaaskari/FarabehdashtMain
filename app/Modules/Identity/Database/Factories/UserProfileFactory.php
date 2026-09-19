<?php

declare(strict_types=1);

namespace App\Modules\Identity\Database\Factories;

use App\Models\User;
use App\Modules\Identity\Domain\Enums\ProfileStatus;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Identity\Domain\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserProfile>
 */
final class UserProfileFactory extends Factory
{
    protected $model = UserProfile::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => ProfileType::Vendor,
            'status' => ProfileStatus::Pending,
            'requested_at' => now(),
        ];
    }

    public function ofType(ProfileType $type): static
    {
        return $this->state(fn (): array => ['type' => $type]);
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => ProfileStatus::Active,
            'approved_at' => now(),
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (): array => ['status' => ProfileStatus::Disabled]);
    }

    public function suspended(): static
    {
        return $this->state(fn (): array => ['status' => ProfileStatus::Suspended]);
    }
}
