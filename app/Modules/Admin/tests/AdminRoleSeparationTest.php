<?php

declare(strict_types=1);

namespace App\Modules\Admin\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Actions\RevokeAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Admin\Services\AdminAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

/**
 * معیار پذیرش بخش ۵: «مدیر محتوا به بخش مالی دسترسی ندارد و بالعکس.»
 */
final class AdminRoleSeparationTest extends TestCase
{
    use RefreshDatabase;

    private AdminAccess $access;

    protected function setUp(): void
    {
        parent::setUp();

        $this->access = $this->app->make(AdminAccess::class);
    }

    private function adminWith(AdminRole ...$roles): User
    {
        $user = User::factory()->create();

        foreach ($roles as $role) {
            $this->app->make(GrantAdminRole::class)->handle($user, $role);
        }

        return $user->fresh() ?? $user;
    }

    public function test_a_content_admin_cannot_touch_money(): void
    {
        $admin = $this->adminWith(AdminRole::Content);

        $this->assertTrue($this->access->allows($admin, 'admin.content.publish'));

        foreach (['admin.wallet.manage', 'admin.settlement.approve', 'admin.refund.issue', 'admin.finance.reports'] as $ability) {
            $this->assertFalse($this->access->allows($admin, $ability), "مدیر محتوا نباید {$ability} داشته باشد.");
        }
    }

    public function test_a_finance_admin_cannot_publish_content(): void
    {
        $admin = $this->adminWith(AdminRole::Finance);

        $this->assertTrue($this->access->allows($admin, 'admin.settlement.approve'));

        foreach (['admin.content.publish', 'admin.content.review', 'admin.chemicals.manage', 'admin.taxonomy.manage'] as $ability) {
            $this->assertFalse($this->access->allows($admin, $ability), "مدیر مالی نباید {$ability} داشته باشد.");
        }
    }

    public function test_a_jobs_admin_has_neither_money_nor_content(): void
    {
        $admin = $this->adminWith(AdminRole::Jobs);

        $this->assertTrue($this->access->allows($admin, 'admin.jobs.review'));
        $this->assertFalse($this->access->allows($admin, 'admin.content.publish'));
        $this->assertFalse($this->access->allows($admin, 'admin.wallet.manage'));
    }

    public function test_only_the_super_admin_can_grant_roles_or_impersonate(): void
    {
        foreach ([AdminRole::Content, AdminRole::Finance, AdminRole::Jobs] as $role) {
            $admin = $this->adminWith($role);

            $this->assertFalse($this->access->allows($admin, 'admin.roles.grant'));
            $this->assertFalse($this->access->allows($admin, 'admin.impersonate'));
            $this->assertFalse($this->access->allows($admin, 'admin.settings.manage'));
        }

        $super = $this->adminWith(AdminRole::Super);

        $this->assertTrue($this->access->allows($super, 'admin.roles.grant'));
        $this->assertTrue($this->access->allows($super, 'admin.impersonate'));
    }

    public function test_the_super_admin_covers_every_other_role(): void
    {
        $super = $this->adminWith(AdminRole::Super);
        $abilities = $this->access->abilitiesOf($super);

        foreach ([AdminRole::Content, AdminRole::Finance, AdminRole::Jobs] as $role) {
            foreach ($role->abilities() as $ability) {
                $this->assertContains($ability, $abilities);
            }
        }
    }

    public function test_two_roles_on_one_account_are_added_together(): void
    {
        $admin = $this->adminWith(AdminRole::Content, AdminRole::Jobs);

        $this->assertTrue($this->access->allows($admin, 'admin.content.publish'));
        $this->assertTrue($this->access->allows($admin, 'admin.jobs.review'));
        $this->assertFalse($this->access->allows($admin, 'admin.wallet.manage'));
    }

    public function test_every_role_can_read_the_audit_log(): void
    {
        // دفتری که فقط یک نفر ببیندش، کنترل نیست.
        foreach (AdminRole::cases() as $role) {
            $this->assertContains('admin.audit.view', $role->abilities(), $role->label());
        }
    }

    public function test_a_user_without_an_admin_role_is_not_an_admin(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->access->isAdmin($user));
        $this->assertSame([], $this->access->abilitiesOf($user));
    }

    /** @return iterable<string, array{AdminRole}> */
    public static function everyRole(): iterable
    {
        foreach (AdminRole::cases() as $role) {
            yield $role->value => [$role];
        }
    }

    #[DataProvider('everyRole')]
    public function test_every_role_can_open_the_panel(AdminRole $role): void
    {
        $this->assertTrue($this->access->isAdmin($this->adminWith($role)));
    }

    public function test_gates_mirror_the_access_service(): void
    {
        $content = $this->adminWith(AdminRole::Content);
        $finance = $this->adminWith(AdminRole::Finance);

        $this->assertTrue($content->can('admin.content.publish'));
        $this->assertFalse($content->can('admin.refund.issue'));

        $this->assertTrue($finance->can('admin.refund.issue'));
        $this->assertFalse($finance->can('admin.content.publish'));
    }

    public function test_revoking_a_role_removes_its_abilities(): void
    {
        $admin = $this->adminWith(AdminRole::Content, AdminRole::Finance);

        $this->app->make(RevokeAdminRole::class)->handle($admin, AdminRole::Finance);

        $this->assertTrue($this->access->allows($admin, 'admin.content.publish'));
        $this->assertFalse($this->access->allows($admin, 'admin.refund.issue'));
    }

    public function test_the_last_super_admin_cannot_be_removed(): void
    {
        // سایت بدون مدیر ارشد، سایتی است که هیچ‌کس دیگر نمی‌تواند نقشی اعطا کند.
        $super = $this->adminWith(AdminRole::Super);

        $this->expectException(RuntimeException::class);

        $this->app->make(RevokeAdminRole::class)->handle($super, AdminRole::Super);
    }

    public function test_a_second_super_admin_makes_the_first_removable(): void
    {
        $first = $this->adminWith(AdminRole::Super);
        $this->adminWith(AdminRole::Super);

        $this->assertTrue($this->app->make(RevokeAdminRole::class)->handle($first, AdminRole::Super));
        $this->assertFalse($this->access->isAdmin($first));
    }
}
