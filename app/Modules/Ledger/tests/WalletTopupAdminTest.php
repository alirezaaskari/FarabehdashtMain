<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Ledger\Filament\Pages\WalletTopupPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * دسترسی صفحه شارژ کیف پول در پنل مدیریت.
 *
 * منطق ثبت تراکنش خودش در LedgerServiceTest و CreditWalletManuallyTest
 * آزموده شده؛ این‌جا فقط دروازه دسترسی و بالاآمدن صفحه سنجیده می‌شود.
 */
final class WalletTopupAdminTest extends TestCase
{
    use RefreshDatabase;

    private function adminWith(AdminRole $role): User
    {
        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, $role);

        return $user->fresh() ?? $user;
    }

    public function test_only_finance_and_super_admins_manage_the_wallet(): void
    {
        $this->assertTrue($this->adminWith(AdminRole::Finance)->can(WalletTopupPage::ABILITY));
        $this->assertTrue($this->adminWith(AdminRole::Super)->can(WalletTopupPage::ABILITY));
        $this->assertFalse($this->adminWith(AdminRole::Content)->can(WalletTopupPage::ABILITY));
        $this->assertFalse(User::factory()->create()->can(WalletTopupPage::ABILITY));
    }

    public function test_a_finance_admin_can_open_the_topup_page(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Finance))
            ->get('/'.config('admin.path').'/wallet-topup')
            ->assertOk()
            ->assertSee('شارژ دستی کیف پول');
    }

    public function test_a_content_admin_cannot_open_the_topup_page(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Content))
            ->get('/'.config('admin.path').'/wallet-topup')
            ->assertForbidden();
    }
}
