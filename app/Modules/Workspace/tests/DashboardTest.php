<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Tests;

use App\Models\User;
use App\Modules\Identity\Actions\DeactivateProfile;
use App\Modules\Identity\Actions\RequestProfileActivation;
use App\Modules\Identity\Actions\ReviewProfileRequest;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Workspace\Domain\WorkspacePreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * میزکار مبتنی بر پروفایل فعال (DEC-21).
 */
final class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $this->get(route('workspace.dashboard'))->assertRedirect(route('login'));
    }

    public function test_the_personal_view_gathers_cards_from_every_module(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('workspace.dashboard'))
            ->assertOk()
            ->assertSee('اعلان‌ها')
            ->assertSee('کیف پول')
            ->assertSee('محاسبات ذخیره‌شده')
            ->assertSee('پروژه‌های اندازه‌گیری')
            ->assertSee('در حال یادگیری')
            ->assertSee('خریدهای من')
            // بدون پروفایل فعال، سوییچری هم نیست.
            ->assertDontSee('نمای میزکار');
    }

    public function test_a_user_with_two_active_profiles_sees_two_different_workspaces(): void
    {
        $user = $this->withProfiles(ProfileType::Vendor, ProfileType::Instructor);

        $this->actingAs($user)
            ->get(route('workspace.dashboard'))
            ->assertOk()
            ->assertSee('نمای میزکار')
            ->assertSee('فروشنده')
            ->assertSee('مدرس');

        $this->actingAs($user)->post(route('workspace.view'), ['view' => 'vendor'])->assertRedirect(route('workspace.dashboard'));

        // عنوان کارت‌ها در ستون کناری هم هست؛ دکمه هر کارت یکتاست.
        $this->get(route('workspace.dashboard'))
            ->assertSee('مدیریت محصولات')
            ->assertDontSee('مدیریت دوره‌ها')
            ->assertDontSee('خریدهای من');

        $this->post(route('workspace.view'), ['view' => 'instructor']);

        $this->get(route('workspace.dashboard'))
            ->assertSee('مدیریت دوره‌ها')
            ->assertDontSee('مدیریت محصولات');
    }

    public function test_the_chosen_view_is_stored_in_the_database_not_the_session(): void
    {
        $user = $this->withProfiles(ProfileType::Vendor);

        $this->actingAs($user)->post(route('workspace.view'), ['view' => 'vendor']);

        $this->assertSame('vendor', WorkspacePreference::query()->where('user_id', $user->id)->value('view'));
    }

    public function test_a_user_cannot_switch_to_a_profile_they_do_not_have(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('workspace.view'), ['view' => 'vendor'])
            ->assertSessionHasErrors('view');

        $this->assertFalse(WorkspacePreference::query()->where('user_id', $user->id)->exists());
    }

    public function test_a_deactivated_profile_falls_back_to_the_personal_view(): void
    {
        $user = $this->withProfiles(ProfileType::Vendor);
        $this->actingAs($user)->post(route('workspace.view'), ['view' => 'vendor']);

        $this->app->make(DeactivateProfile::class)->handle($user, ProfileType::Vendor);

        $this->actingAs($user->fresh() ?? $user)
            ->get(route('workspace.dashboard'))
            ->assertOk()
            ->assertSee('خریدهای من')
            ->assertDontSee('مدیریت محصولات');
    }

    public function test_the_vendor_panel_now_sits_inside_the_workspace_shell(): void
    {
        $this->actingAs($this->withProfiles(ProfileType::Vendor))
            ->get(route('commerce.vendor.products.index'))
            ->assertOk()
            ->assertSee('aria-current="page"', escape: false)
            ->assertSee('نقش‌ها و پروفایل‌ها');
    }

    private function withProfiles(ProfileType ...$types): User
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();

        foreach ($types as $type) {
            $profile = $this->app->make(RequestProfileActivation::class)->handle($user, $type);
            $this->app->make(ReviewProfileRequest::class)->approve($profile, $admin);
        }

        return $user->fresh() ?? $user;
    }
}
