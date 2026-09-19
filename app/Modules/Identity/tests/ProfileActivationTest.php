<?php

declare(strict_types=1);

namespace App\Modules\Identity\Tests;

use App\Models\User;
use App\Modules\Identity\Actions\ReviewProfileRequest;
use App\Modules\Identity\Domain\Enums\ProfileStatus;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Identity\Domain\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * معیار پذیرش بخش ۳:
 * «فعال‌سازی پروفایل نیازمند تأیید مدیر است؛ غیرفعال‌کردن پروفایل داده حذف نمی‌کند.»
 */
final class ProfileActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_account_has_no_business_profile(): void
    {
        $user = User::factory()->create();

        $this->assertCount(0, $user->profiles);
        $this->assertFalse($user->hasActiveProfile(ProfileType::Vendor));
    }

    public function test_requesting_a_profile_leaves_it_waiting_for_the_administrator(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('identity.profiles.activate', ProfileType::Vendor->value))
            ->assertRedirect();

        $profile = $user->fresh()?->profileFor(ProfileType::Vendor);

        $this->assertInstanceOf(UserProfile::class, $profile);
        $this->assertSame(ProfileStatus::Pending, $profile->status);
        $this->assertNull($profile->approved_at);
        $this->assertFalse($profile->grantsAccess(), 'پروفایل در انتظار تأیید نباید دسترسی بدهد.');
    }

    public function test_a_pending_profile_grants_no_permission_until_the_administrator_approves(): void
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->for($user)->ofType(ProfileType::Vendor)->create();

        $this->assertFalse($user->can('products.manage'));

        $admin = User::factory()->create();
        app(ReviewProfileRequest::class)->approve($profile, $admin);

        $user = $user->fresh();
        $this->assertNotNull($user);
        $this->assertSame(ProfileStatus::Active, $user->profileStatus(ProfileType::Vendor));
        $this->assertTrue($user->can('products.manage'));
    }

    public function test_a_rejected_profile_keeps_the_administrator_note(): void
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->for($user)->ofType(ProfileType::Instructor)->create();
        $admin = User::factory()->create();

        app(ReviewProfileRequest::class)->reject($profile, $admin, 'مدرک آموزشی خوانا نبود.');

        $profile->refresh();

        $this->assertSame(ProfileStatus::Disabled, $profile->status);
        $this->assertSame('مدرک آموزشی خوانا نبود.', $profile->rejection_note);
        $this->assertSame($admin->getKey(), $profile->approved_by);
        $this->assertFalse($user->fresh()?->can('courses.manage'));
    }

    public function test_deactivating_a_profile_never_deletes_the_row(): void
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->for($user)->ofType(ProfileType::Vendor)->active()->create([
            'meta' => ['shop_name' => 'ایمن‌کار'],
        ]);

        $this->actingAs($user)
            ->post(route('identity.profiles.deactivate', ProfileType::Vendor->value))
            ->assertRedirect();

        $profile->refresh();

        $this->assertSame(ProfileStatus::Disabled, $profile->status);
        $this->assertSame(['shop_name' => 'ایمن‌کار'], $profile->meta);
        $this->assertSame(1, UserProfile::query()->count(), 'غیرفعال‌کردن نباید رکورد را حذف کند.');
        $this->assertFalse($user->fresh()?->can('products.manage'));
    }

    public function test_reactivating_a_disabled_profile_goes_back_to_the_review_queue(): void
    {
        $user = User::factory()->create();
        UserProfile::factory()->for($user)->ofType(ProfileType::Vendor)->disabled()->create();

        $this->actingAs($user)
            ->post(route('identity.profiles.activate', ProfileType::Vendor->value))
            ->assertRedirect();

        $this->assertSame(
            ProfileStatus::Pending,
            $user->fresh()?->profileStatus(ProfileType::Vendor),
            'نقش قبلاً تأییدشده هم بدون بررسی دوباره فعال نمی‌شود.',
        );
    }

    public function test_an_unknown_profile_type_is_not_found(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('identity.profiles.activate', 'inspector'))
            ->assertNotFound();
    }

    public function test_a_guest_cannot_reach_the_profiles_page(): void
    {
        $this->get(route('identity.profiles'))->assertRedirect(route('login'));
    }
}
