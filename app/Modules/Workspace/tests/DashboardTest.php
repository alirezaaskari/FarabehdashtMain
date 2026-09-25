<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Tests;

use App\Models\User;
use App\Modules\Identity\Actions\DeactivateProfile;
use App\Modules\Identity\Actions\RequestProfileActivation;
use App\Modules\Identity\Actions\ReviewProfileRequest;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Workspace\Domain\WorkspacePreference;
use App\Modules\Workspace\Services\Dashboard;
use App\Support\Workspace\WidgetRow;
use App\Support\Workspace\WidgetStat;
use App\Support\Workspace\WorkspaceWidget;
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

    public function test_the_sidebar_groups_every_module_under_three_headings(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('workspace.dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['کار من', 'محاسبات ذخیره‌شده', 'یادگیری و خرید', 'دوره‌های من', 'حساب', 'حساب من'])
            ->assertSee('اعلان‌ها')
            ->assertSee('کیف پول')
            ->assertSee('محاسبات ذخیره‌شده')
            ->assertSee('پروژه‌های اندازه‌گیری')
            ->assertSee('خریدهای من')
            // بدون پروفایل فعال، سوییچری هم نیست.
            ->assertDontSee('نمای میزکار');
    }

    public function test_a_new_user_sees_first_steps_instead_of_an_empty_activity_list(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'مریم']))
            ->get(route('workspace.dashboard'))
            ->assertOk()
            ->assertSee('سلام، مریم')
            // پیمایش کار ستون کناری است و در بدنه تکرار نمی‌شود.
            ->assertDontSee('دسترسی سریع')
            ->assertSee('شروع کار با میزکار')
            ->assertDontSee('آخرین فعالیت‌ها');
    }

    public function test_recent_activity_takes_turns_between_cards(): void
    {
        // یک ماژول پرکار نباید بقیه را از «آخرین فعالیت‌ها» بیرون کند.
        $rows = static fn (string $prefix, int $count): array => array_map(
            static fn (int $i): WidgetRow => new WidgetRow($prefix.$i),
            range(1, $count),
        );

        $widgets = [
            new WorkspaceWidget('a', 'الف', 10, [new WidgetStat('شمار', '۳')], $rows('a', 5)),
            new WorkspaceWidget('b', 'ب', 20, [], $rows('b', 1)),
            new WorkspaceWidget('c', 'ج', 30, [new WidgetStat('شمار', '۲')], $rows('c', 2)),
        ];

        $dashboard = new Dashboard([]);

        $this->assertSame(
            ['a1', 'b1', 'c1', 'a2', 'c2', 'a3'],
            array_map(static fn (WidgetRow $row): string => $row->label, $dashboard->activity($widgets)),
        );
        $this->assertSame(['الف', 'ج'], array_map(static fn (WidgetStat $s): string => $s->label, $dashboard->highlights($widgets)));
    }

    public function test_a_zero_stat_stays_out_of_the_highlights(): void
    {
        $widgets = [
            new WorkspaceWidget('a', 'صفر', 10, [new WidgetStat('شمار', '۰')]),
            new WorkspaceWidget('b', 'مبلغ صفر', 20, [new WidgetStat('موجودی', '۰ تومان')]),
            new WorkspaceWidget('c', 'ده', 30, [new WidgetStat('شمار', '۱۰')]),
        ];

        $this->assertSame(
            ['ده'],
            array_map(static fn (WidgetStat $s): string => $s->label, (new Dashboard([]))->highlights($widgets)),
        );
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
            ->assertDontSee('شروع کار با میزکار');

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
