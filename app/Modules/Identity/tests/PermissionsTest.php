<?php

declare(strict_types=1);

namespace App\Modules\Identity\Tests;

use App\Models\User;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Identity\Domain\UserProfile;
use App\Modules\Identity\Services\PermissionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * قاعده محصول: مجوزها جمع‌شونده‌اند و فقط پروفایل فعال مجوز اضافه می‌کند.
 */
final class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    private PermissionResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = $this->app->make(PermissionResolver::class);
    }

    public function test_every_signed_in_user_has_the_base_permissions(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->resolver->allows($user, 'tools.use'));
        $this->assertTrue($this->resolver->allows($user, 'jobs.apply'));
        $this->assertFalse($this->resolver->allows($user, 'products.manage'));
    }

    public function test_permissions_of_several_active_profiles_are_added_together(): void
    {
        $user = User::factory()->create();
        UserProfile::factory()->for($user)->ofType(ProfileType::Vendor)->active()->create();
        UserProfile::factory()->for($user)->ofType(ProfileType::Consultant)->active()->create();

        $permissions = $this->resolver->for($user->fresh() ?? $user);

        $this->assertContains('products.manage', $permissions);
        $this->assertContains('consulting.services.manage', $permissions);
        $this->assertContains('tools.use', $permissions);
        $this->assertNotContains('jobs.post', $permissions);
    }

    public function test_a_shared_permission_appears_only_once(): void
    {
        $user = User::factory()->create();
        UserProfile::factory()->for($user)->ofType(ProfileType::Vendor)->active()->create();
        UserProfile::factory()->for($user)->ofType(ProfileType::Instructor)->active()->create();

        $permissions = $this->resolver->for($user->fresh() ?? $user);

        // فروشنده و مدرس هر دو گزارش فروش دارند.
        $this->assertSame([...array_unique($permissions)], $permissions);
        $this->assertSame(1, count(array_keys($permissions, 'sales.reports', strict: true)));
    }

    /**
     * @return iterable<string, array{ProfileType}>
     */
    public static function inactiveStates(): iterable
    {
        yield 'pending' => ['pending'];
        yield 'suspended' => ['suspended'];
        yield 'disabled' => ['disabled'];
    }

    #[DataProvider('inactiveStates')]
    public function test_a_profile_that_is_not_active_grants_nothing(string $state): void
    {
        $user = User::factory()->create();
        $factory = UserProfile::factory()->for($user)->ofType(ProfileType::Employer);

        $profile = match ($state) {
            'suspended' => $factory->suspended(),
            'disabled' => $factory->disabled(),
            default => $factory,
        };

        $profile->create();

        $this->assertFalse($this->resolver->allows($user->fresh() ?? $user, 'jobs.post'));
    }

    public function test_every_known_permission_is_registered_as_a_gate(): void
    {
        $user = User::factory()->create();
        UserProfile::factory()->for($user)->ofType(ProfileType::Employer)->active()->create();
        $user = $user->fresh();

        $this->assertNotNull($user);
        $this->assertTrue($user->can('jobs.post'));
        $this->assertFalse($user->can('products.manage'));
    }

    public function test_an_unknown_permission_is_never_allowed(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->resolver->allows($user, 'organization.risk_assessment'));
        $this->assertNotContains('organization.risk_assessment', $this->resolver->known());
    }
}
