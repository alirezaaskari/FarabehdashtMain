<?php

declare(strict_types=1);

namespace App\Modules\Admin\Tests;

use App\Contracts\AuditTrail;
use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Admin\Services\Impersonation;
use App\Support\Audit\AuditEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * مشاهده میزکار از چشم کاربر.
 *
 * اصل ۷ سند پنل مدیریت: با ثبت اجباری و بدون امکان عملیات مالی.
 */
final class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private FakeTrail $trail;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trail = new FakeTrail;
        $this->app->instance(AuditTrail::class, $this->trail);
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($admin, AdminRole::Super);

        return $admin->fresh() ?? $admin;
    }

    private function contentAdmin(): User
    {
        $admin = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($admin, AdminRole::Content);

        return $admin->fresh() ?? $admin;
    }

    public function test_a_super_admin_can_see_the_site_as_a_user(): void
    {
        $admin = $this->superAdmin();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.impersonate.start', $target), ['reason' => 'بررسی گزارش خطای کاربر در ابزار صدا'])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($target);
    }

    public function test_the_reason_is_mandatory_and_lands_in_the_audit_log(): void
    {
        $admin = $this->superAdmin();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.impersonate.start', $target), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertAuthenticatedAs($admin, 'web');
        $this->assertNotContains('admin.impersonation_started', $this->trail->actions());

        $this->actingAs($admin)
            ->post(route('admin.impersonate.start', $target), ['reason' => 'بررسی گزارش خطای کاربر در ابزار صدا']);

        $entry = $this->trail->last();

        $this->assertSame('admin.impersonation_started', $entry->action);
        $this->assertSame($admin->getKey(), $entry->actorId);
        $this->assertSame((string) $target->getKey(), (string) $entry->subjectId);
        $this->assertSame('بررسی گزارش خطای کاربر در ابزار صدا', $entry->context['reason']);
    }

    public function test_a_one_word_reason_is_refused(): void
    {
        // «تست» دلیل نیست؛ شش ماه بعد هیچ‌کس نمی‌فهمد چرا این اتفاق افتاده.
        $this->actingAs($this->superAdmin())
            ->post(route('admin.impersonate.start', User::factory()->create()), ['reason' => 'تست'])
            ->assertSessionHasErrors('reason');
    }

    public function test_an_admin_without_the_ability_cannot_impersonate(): void
    {
        $this->actingAs($this->contentAdmin())
            ->post(route('admin.impersonate.start', User::factory()->create()), ['reason' => 'دلیل کافی برای بررسی'])
            ->assertForbidden();
    }

    public function test_an_ordinary_user_cannot_impersonate(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.impersonate.start', User::factory()->create()), ['reason' => 'دلیل کافی برای بررسی'])
            ->assertForbidden();
    }

    public function test_no_financial_action_is_allowed_while_impersonating(): void
    {
        $impersonation = $this->app->make(Impersonation::class);

        $impersonation->start($this->superAdmin(), User::factory()->create(), 'بررسی گزارش خطای کاربر');

        $this->expectException(RuntimeException::class);

        $impersonation->guardAgainstFinancialAction();
    }

    public function test_the_financial_guard_is_silent_when_no_impersonation_is_active(): void
    {
        $this->app->make(Impersonation::class)->guardAgainstFinancialAction();

        $this->assertFalse($this->app->make(Impersonation::class)->isActive());
    }

    public function test_the_admin_can_always_come_back(): void
    {
        $admin = $this->superAdmin();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.impersonate.start', $target), ['reason' => 'بررسی گزارش خطای کاربر در ابزار صدا']);

        $this->assertAuthenticatedAs($target);

        $this->post(route('admin.impersonate.stop'))->assertRedirect();

        $this->assertAuthenticatedAs($admin);
        $this->assertSame('admin.impersonation_stopped', $this->trail->last()->action);
    }

    public function test_coming_back_needs_no_admin_ability(): void
    {
        // کسی که در این حالت گیر کرده باید همیشه بتواند برگردد، حتی اگر نقشش
        // وسط کار عوض شده باشد.
        $routes = $this->app->make('router')->getRoutes();
        $stop = $routes->getByName('admin.impersonate.stop');

        $this->assertNotNull($stop);
        $this->assertNotContains('admin.ability:admin.impersonate', $stop->gatherMiddleware());
    }

    public function test_impersonating_yourself_is_refused(): void
    {
        $admin = $this->superAdmin();

        $this->expectException(RuntimeException::class);

        $this->app->make(Impersonation::class)->start($admin, $admin, 'دلیل کافی برای بررسی');
    }

    public function test_a_second_impersonation_cannot_start_on_top_of_the_first(): void
    {
        $impersonation = $this->app->make(Impersonation::class);
        $admin = $this->superAdmin();

        $impersonation->start($admin, User::factory()->create(), 'بررسی گزارش خطای کاربر');

        $this->expectException(RuntimeException::class);

        $impersonation->start($admin, User::factory()->create(), 'بررسی دوم');
    }

    public function test_stopping_without_an_active_impersonation_is_refused(): void
    {
        $this->expectException(RuntimeException::class);

        $this->app->make(Impersonation::class)->stop();
    }
}

/** دفتر ساختگی: فقط نگه می‌دارد چه چیزی قرار بود ثبت شود. */
final class FakeTrail implements AuditTrail
{
    /** @var list<AuditEntry> */
    private array $entries = [];

    public function record(AuditEntry $entry): void
    {
        $this->entries[] = $entry;
    }

    public function last(): AuditEntry
    {
        return $this->entries[array_key_last($this->entries)];
    }

    /** @return list<string> */
    public function actions(): array
    {
        return array_map(static fn (AuditEntry $entry): string => $entry->action, $this->entries);
    }
}
