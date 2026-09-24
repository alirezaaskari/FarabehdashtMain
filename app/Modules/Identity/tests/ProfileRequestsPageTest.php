<?php

declare(strict_types=1);

namespace App\Modules\Identity\Tests;

use App\Models\User;
use App\Modules\Admin\Actions\GrantAdminRole;
use App\Modules\Admin\Domain\Enums\AdminRole;
use App\Modules\Identity\Domain\Enums\ProfileStatus;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Identity\Domain\UserProfile;
use App\Modules\Identity\Filament\Pages\ProfileRequestsPage;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * صف تأیید نقش در پنل: بدون آن هیچ درخواست فروشنده یا مدرسی روی سایت
 * زنده به نتیجه نمی‌رسید.
 */
final class ProfileRequestsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Livewire::test صفحه را بیرون از مسیر پنل می‌سازد؛ پنل جاری را خودمان می‌گذاریم.
        Filament::setCurrentPanel(Filament::getPanel('fbh'));
    }

    private function adminWith(AdminRole $role): User
    {
        $user = User::factory()->create();
        $this->app->make(GrantAdminRole::class)->handle($user, $role);

        return $user->fresh() ?? $user;
    }

    private function pendingVendor(): UserProfile
    {
        return UserProfile::factory()
            ->for(User::factory()->create(['name' => 'فروشنده آزمایشی']))
            ->ofType(ProfileType::Vendor)
            ->create();
    }

    public function test_a_content_admin_sees_pending_requests(): void
    {
        $this->pendingVendor();

        $this->actingAs($this->adminWith(AdminRole::Content))
            ->get('/'.config('admin.path').'/profile-requests')
            ->assertOk()
            ->assertSee('فروشنده آزمایشی');
    }

    public function test_a_finance_admin_cannot_open_the_page(): void
    {
        $this->actingAs($this->adminWith(AdminRole::Finance))
            ->get('/'.config('admin.path').'/profile-requests')
            ->assertForbidden();
    }

    public function test_approving_grants_the_vendor_permissions(): void
    {
        $profile = $this->pendingVendor();

        $this->actingAs($this->adminWith(AdminRole::Content));

        Livewire::test(ProfileRequestsPage::class)
            ->call('approve', $profile->id)
            ->assertSet('rows', []);

        $this->assertSame(ProfileStatus::Active, $profile->fresh()?->status);
        $this->assertTrue($profile->user->fresh()?->can('products.manage'));
    }

    public function test_rejecting_needs_a_note_and_keeps_it_for_the_user(): void
    {
        $profile = $this->pendingVendor();

        $this->actingAs($this->adminWith(AdminRole::Content));

        $page = Livewire::test(ProfileRequestsPage::class)->call('reject', $profile->id);

        $this->assertSame(ProfileStatus::Pending, $profile->fresh()?->status);

        $page->set("rejectNotes.{$profile->id}", 'نمونه کار پیوست نشده بود.')
            ->call('reject', $profile->id);

        $profile->refresh();
        $this->assertSame(ProfileStatus::Disabled, $profile->status);
        $this->assertSame('نمونه کار پیوست نشده بود.', $profile->rejection_note);
    }

    public function test_an_already_reviewed_profile_is_not_reviewed_again(): void
    {
        $profile = $this->pendingVendor();
        $profile->forceFill(['status' => ProfileStatus::Suspended->value])->save();

        $this->actingAs($this->adminWith(AdminRole::Content));

        Livewire::test(ProfileRequestsPage::class)->call('approve', $profile->id);

        $this->assertSame(ProfileStatus::Suspended, $profile->fresh()?->status);
    }
}
