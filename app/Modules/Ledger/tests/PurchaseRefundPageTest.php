<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Tests;

use App\Contracts\WalletStatementReader;
use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Core\Domain\AuditLog;
use App\Modules\ExamPrep\Domain\PackPurchase;
use App\Modules\ExamPrep\Tests\ExamFixtures;
use App\Modules\Ledger\Actions\CreditWalletManually;
use App\Modules\Ledger\Filament\Pages\PurchaseRefundPage;
use App\Support\Money;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * صفحه «بازگشت وجه خریدهای دیگر»: پیدا کردن با موبایل، دلیل اجباری، تأیید
 * جدا از اجرا و ثبت در دفتر رویداد.
 */
final class PurchaseRefundPageTest extends TestCase
{
    use ExamFixtures;
    use RefreshDatabase;

    public function test_the_finance_admin_refunds_after_a_reason_and_a_confirmation(): void
    {
        $pack = $this->pack();
        $buyer = User::factory()->create(['mobile' => '09121234567']);
        $this->app->make(CreditWalletManually::class)->handle($buyer->id, Money::toman(190_000), null);
        $this->actingAs($buyer)->post(route('exam_prep.purchase', $pack->slug), ['payment' => 'wallet']);
        $uuid = PackPurchase::query()->sole()->uuid;

        $this->actingAs($this->admin(AdminRole::Finance));

        $page = Livewire::test(PurchaseRefundPage::class)
            ->set('mobile', '۰۹۱۲۱۲۳۴۵۶۷')
            ->call('find')
            ->assertSet('error', null)
            ->assertSee('بسته «آزمون استخدامی بهداشت حرفه‌ای»')
            ->call('confirm', $uuid)
            ->assertSet('confirming', null)
            ->set('reasons.'.$uuid, 'خرید تکراری کاربر')
            ->call('confirm', $uuid)
            ->assertSet('confirming', $uuid)
            ->call('refund', 'PackPurchaseRefunds', $uuid)
            ->assertSet('error', null);

        $page->assertSee('برگشت خورده');
        $this->assertSame(190_000, $this->app->make(WalletStatementReader::class)->balanceOf($buyer->id)->toman);
        $this->assertSame(1, AuditLog::query()->where('action', 'ledger.purchase_refunded')->count());
    }

    public function test_an_unknown_mobile_is_reported(): void
    {
        $this->actingAs($this->admin(AdminRole::Finance));

        Livewire::test(PurchaseRefundPage::class)
            ->set('mobile', '09120000000')
            ->call('find')
            ->assertSet('error', 'کاربری با این شماره موبایل پیدا نشد.');
    }

    public function test_the_content_admin_cannot_open_the_page(): void
    {
        $this->actingAs($this->admin(AdminRole::Content))
            ->get(route('filament.fbh.pages.purchase-refunds'))
            ->assertForbidden();
    }

    private function admin(AdminRole $role): User
    {
        Filament::setCurrentPanel(Filament::getPanel('fbh'));

        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, $role);

        return $user->fresh() ?? $user;
    }
}
