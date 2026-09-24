<?php

declare(strict_types=1);

namespace App\Modules\Admin\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * دسترسی به خود پنل.
 */
final class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    private function panelPath(): string
    {
        return '/'.trim((string) config('admin.path'), '/');
    }

    private function adminWith(AdminRole $role): User
    {
        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, $role);

        return $user->fresh() ?? $user;
    }

    public function test_the_panel_path_comes_from_configuration_and_is_not_admin(): void
    {
        // DEC-09: مسیر غیرقابل‌حدس، قابل تغییر با متغیر محیطی.
        $this->assertSame('fbh-panel', config('admin.path'));
        $this->get('/admin')->assertNotFound();
    }

    public function test_a_guest_is_sent_to_the_site_login_page(): void
    {
        // حساب مدیر رمز ندارد؛ فرم ایمیل و رمز Filament بن‌بست بود.
        $this->get($this->panelPath())->assertRedirect(route('login'));
        $this->get($this->panelPath().'/login')->assertNotFound();
    }

    public function test_a_signed_in_user_without_an_admin_role_is_refused(): void
    {
        // ۴۰۳ است نه ۴۰۴، چون صفحه ورودِ پنل به هر حال وجود مسیر را لو می‌دهد؛
        // پنهان‌کردن با ۴۰۴ فقط ظاهرسازی می‌شد. پنهانی واقعی از مسیر
        // غیرقابل‌حدس می‌آید، نه از کد وضعیت.
        $this->actingAs(User::factory()->create())
            ->get($this->panelPath())
            ->assertForbidden();
    }

    public function test_an_admin_reaches_the_dashboard(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Content))
            ->get($this->panelPath())
            ->assertOk()
            ->assertSee('چه چیزی معطل شماست؟');
    }

    public function test_the_dashboard_shows_the_role_of_the_signed_in_admin(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Finance))
            ->get($this->panelPath())
            ->assertOk()
            ->assertSee('مدیر مالی');
    }

    public function test_an_empty_queue_says_so_plainly(): void
    {
        // هیچ ماژول محتوایی هنوز منبع صف ثبت نکرده، پس صف واقعاً خالی است.
        $this->actingAs($this->adminWith(AdminRole::Content))
            ->get($this->panelPath())
            ->assertOk()
            ->assertSee('هیچ چیزی معطل شما نیست.');
    }

    public function test_the_panel_is_rendered_right_to_left_in_persian(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Super))
            ->get($this->panelPath())
            ->assertOk()
            ->assertSee('dir="rtl"', escape: false);
    }

    public function test_the_audit_page_is_open_to_every_admin_role(): void
    {
        foreach (AdminRole::cases() as $role) {
            $this->actingAs($this->adminWith($role))
                ->get($this->panelPath().'/audit')
                ->assertOk();
        }
    }
}
