<?php

declare(strict_types=1);

namespace App\Modules\Admin\Tests;

use App\Contracts\AuditTrail;
use App\Models\User;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Admin\Services\AdminAccess;
use App\Support\Audit\AuditEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * تنها راه ساخت اولین مدیر ارشد (DEC-16).
 */
final class MakeAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_first_super_admin_from_scratch(): void
    {
        $this->artisan('fbh:make-admin', ['mobile' => '09121234567'])->assertSuccessful();

        $user = User::query()->where('mobile', '09121234567')->sole();

        $this->assertTrue($this->app->make(AdminAccess::class)->hasRole($user, AdminRole::Super));
        $this->assertNotNull($user->mobile_verified_at);
        $this->assertNull($user->password, 'ورود با کد یک‌بارمصرف است؛ رمز عبوری ساخته نمی‌شود.');
    }

    public function test_it_promotes_an_existing_account_without_duplicating_it(): void
    {
        $user = User::factory()->create(['mobile' => '09121234567']);

        $this->artisan('fbh:make-admin', ['mobile' => '09121234567', '--role' => 'content'])->assertSuccessful();

        $this->assertSame(1, User::query()->where('mobile', '09121234567')->count());
        $this->assertTrue($this->app->make(AdminAccess::class)->hasRole($user->fresh() ?? $user, AdminRole::Content));
    }

    public function test_it_accepts_a_mobile_number_written_any_way(): void
    {
        $this->artisan('fbh:make-admin', ['mobile' => '+۹۸۹۱۲۱۲۳۴۵۶۷'])->assertSuccessful();

        $this->assertDatabaseHas('users', ['mobile' => '09121234567']);
    }

    public function test_an_invalid_mobile_number_is_refused(): void
    {
        $this->artisan('fbh:make-admin', ['mobile' => '0912'])->assertFailed();

        $this->assertSame(0, User::query()->count());
    }

    public function test_an_unknown_role_is_refused(): void
    {
        $this->artisan('fbh:make-admin', ['mobile' => '09121234567', '--role' => 'wizard'])->assertFailed();
    }

    public function test_granting_the_first_role_is_written_to_the_audit_log(): void
    {
        $trail = new CommandFakeTrail;
        $this->app->instance(AuditTrail::class, $trail);

        $this->artisan('fbh:make-admin', ['mobile' => '09121234567'])->assertSuccessful();

        $entry = $trail->last();

        $this->assertSame('admin.role_granted', $entry->action);
        $this->assertSame(AdminRole::Super->value, $entry->after['role']);
        $this->assertNull($entry->actorId, 'مدیر اول اعطاکننده‌ای ندارد؛ این تنها حالت مجاز است.');
    }

    public function test_running_it_twice_does_not_record_a_second_grant(): void
    {
        $trail = new CommandFakeTrail;
        $this->app->instance(AuditTrail::class, $trail);

        $this->artisan('fbh:make-admin', ['mobile' => '09121234567'])->assertSuccessful();
        $this->artisan('fbh:make-admin', ['mobile' => '09121234567'])->assertSuccessful();

        // دفتر رویداد باید تغییر را نشان بدهد، نه تکرار را.
        $this->assertCount(1, $trail->all());
    }
}

final class CommandFakeTrail implements AuditTrail
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

    /** @return list<AuditEntry> */
    public function all(): array
    {
        return $this->entries;
    }
}
