<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Chemicals\Filament\Pages\ChemicalImportPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * دسترسی صفحه ورود CSV در پنل مدیریت.
 *
 * منطق پیش‌نمایش/اجرا/خروجی خودش در CsvImportTest به‌طور مستقیم روی سرویس‌ها
 * و اکشن آزموده شده؛ این‌جا فقط دروازه دسترسی و بالاآمدن صفحه را می‌سنجد —
 * همان تفکیکی که ToolAdminTest برای صفحه ابزارها دارد.
 */
final class ChemicalAdminTest extends TestCase
{
    use RefreshDatabase;

    private function adminWith(AdminRole $role): User
    {
        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, $role);

        return $user->fresh() ?? $user;
    }

    public function test_only_content_and_super_admins_manage_chemicals(): void
    {
        $this->assertTrue($this->adminWith(AdminRole::Content)->can(ChemicalImportPage::ABILITY));
        $this->assertTrue($this->adminWith(AdminRole::Super)->can(ChemicalImportPage::ABILITY));
        $this->assertFalse($this->adminWith(AdminRole::Finance)->can(ChemicalImportPage::ABILITY));
        $this->assertFalse(User::factory()->create()->can(ChemicalImportPage::ABILITY));
    }

    public function test_a_content_admin_can_open_the_import_page(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Content))
            ->get('/'.config('admin.path').'/chemicals-import')
            ->assertOk()
            ->assertSee('ورود مواد از اکسل (CSV)')
            ->assertSee('راهنمای ورود از اکسل')
            ->assertSee('دریافت قالب اکسل');
    }

    public function test_a_finance_admin_cannot_open_the_import_page(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Finance))
            ->get('/'.config('admin.path').'/chemicals-import')
            ->assertForbidden();
    }
}
