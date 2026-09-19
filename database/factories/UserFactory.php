<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Modules\Identity\Domain\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    protected $model = User::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake('fa_IR')->name(),
            'mobile' => '09'.fake()->unique()->numerify('#########'),
            'mobile_verified_at' => now(),
            'email' => null,
            'password' => null,
            'status' => UserStatus::Active,
            'remember_token' => Str::random(10),
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn (): array => ['status' => UserStatus::Suspended]);
    }

    public function unverified(): static
    {
        return $this->state(fn (): array => ['mobile_verified_at' => null]);
    }

    public function onboarded(): static
    {
        return $this->state(fn (): array => ['onboarded_at' => now()]);
    }
}
