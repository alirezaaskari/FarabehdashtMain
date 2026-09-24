<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * دسترسی صفحه‌های مدیریت — منطق خود کلیدها در RevenueStreamToggleTest است.
 *
 * کلیدها فقط دست مدیر ارشد است، چون خاموش‌کردن یک جریان درآمد را قطع
 * می‌کند؛ مدیر مالی قیمت و مشترکان را می‌بیند ولی کلید را نه.
 */
final class AdminMonetizationPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_super_admin_opens_the_revenue_switches(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Super))
            ->get($this->page('revenue-streams'))
            ->assertOk()
            ->assertSee('اشتراک Pro');
    }

    public function test_a_finance_admin_cannot_flip_revenue_switches(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Finance))
            ->get($this->page('revenue-streams'))
            ->assertForbidden();
    }

    public function test_a_finance_admin_opens_the_subscriptions_page(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Finance))
            ->get($this->page('subscriptions'))
            ->assertOk();
    }

    public function test_a_content_admin_sees_neither_page(): void
    {
        $admin = $this->adminWith(AdminRole::Content);

        $this->actingAs($admin)->get($this->page('revenue-streams'))->assertForbidden();
        $this->actingAs($admin)->get($this->page('subscriptions'))->assertForbidden();
    }

    private function page(string $slug): string
    {
        return '/'.config('admin.path').'/'.$slug;
    }

    private function adminWith(AdminRole $role): User
    {
        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, $role);

        return $user->fresh() ?? $user;
    }
}
