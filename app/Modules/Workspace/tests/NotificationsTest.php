<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Tests;

use App\Models\User;
use App\Modules\Identity\Actions\RequestProfileActivation;
use App\Modules\Identity\Actions\ReviewProfileRequest;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Ledger\Actions\CreditWalletManually;
use App\Modules\Workspace\Domain\UserNotification;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * اعلان‌های درون‌سایتی (DEC-22): از رویدادهای موجود ساخته می‌شوند، نه از
 * فراخوانی مستقیم ماژول‌ها.
 */
final class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_approved_profile_notifies_its_owner(): void
    {
        $user = User::factory()->create();
        $profile = $this->app->make(RequestProfileActivation::class)->handle($user, ProfileType::Vendor);
        $this->app->make(ReviewProfileRequest::class)->approve($profile, User::factory()->create());

        $notification = UserNotification::query()->where('user_id', $user->id)->sole();

        $this->assertSame('profile.approved', $notification->kind);
        $this->assertStringContainsString('فروشنده', $notification->title);
        $this->assertFalse($notification->isRead());
    }

    public function test_a_rejection_carries_the_admin_note(): void
    {
        $user = User::factory()->create();
        $profile = $this->app->make(RequestProfileActivation::class)->handle($user, ProfileType::Vendor);
        $this->app->make(ReviewProfileRequest::class)->reject($profile, User::factory()->create(), 'مدارک ناقص است.');

        $this->assertSame('مدارک ناقص است.', UserNotification::query()->where('user_id', $user->id)->sole()->body);
    }

    public function test_money_never_moves_without_its_owner_hearing_about_it(): void
    {
        $user = User::factory()->create();

        $this->app->make(CreditWalletManually::class)->handle($user->id, Money::toman(250_000), null, 'رسید ۱۲۳');

        $notification = UserNotification::query()->where('user_id', $user->id)->sole();

        $this->assertSame('ledger.wallet_credited', $notification->kind);
        $this->assertStringContainsString('۲۵۰٬۰۰۰ تومان', $notification->title);
        $this->assertSame(route('workspace.wallet'), $notification->url());
    }

    public function test_opening_a_notification_marks_it_read_and_follows_its_link(): void
    {
        $user = User::factory()->create();
        $this->app->make(CreditWalletManually::class)->handle($user->id, Money::toman(1_000), null);
        $notification = UserNotification::query()->where('user_id', $user->id)->sole();

        $this->actingAs($user)
            ->get(route('workspace.notifications.open', $notification->uuid))
            ->assertRedirect(route('workspace.wallet'));

        $this->assertTrue($notification->fresh()?->isRead());
    }

    public function test_nobody_opens_someone_elses_notification(): void
    {
        $owner = User::factory()->create();
        $this->app->make(CreditWalletManually::class)->handle($owner->id, Money::toman(1_000), null);
        $notification = UserNotification::query()->where('user_id', $owner->id)->sole();

        $this->actingAs(User::factory()->create())
            ->get(route('workspace.notifications.open', $notification->uuid))
            ->assertNotFound();

        $this->assertFalse($notification->fresh()?->isRead());
    }

    public function test_a_notification_whose_module_is_gone_stays_readable_without_a_link(): void
    {
        $notification = UserNotification::query()->create([
            'uuid' => '0190a8a4-7b2c-7000-8000-000000000001',
            'user_id' => User::factory()->create()->id,
            'kind' => 'removed.module_event',
            'title' => 'اعلان ماژول حذف‌شده',
            'route_name' => 'removed.module.route',
        ]);

        $this->assertNull($notification->url());

        $this->actingAs($notification->user)
            ->get(route('workspace.notifications.open', $notification->uuid))
            ->assertRedirect(route('workspace.notifications'));
    }

    public function test_the_header_shows_the_unread_count_and_read_all_clears_it(): void
    {
        $user = User::factory()->create();
        $this->app->make(CreditWalletManually::class)->handle($user->id, Money::toman(1_000), null);
        $this->app->make(CreditWalletManually::class)->handle($user->id, Money::toman(2_000), null);

        $this->actingAs($user)
            ->get(route('workspace.notifications'))
            ->assertOk()
            ->assertSee('اعلان‌ها، ۲ خوانده‌نشده')
            ->assertSee('همه را خواندم');

        $this->post(route('workspace.notifications.read-all'))->assertRedirect(route('workspace.notifications'));

        $this->assertSame(0, UserNotification::query()->where('user_id', $user->id)->whereNull('read_at')->count());

        $this->get(route('workspace.notifications'))->assertDontSee('خوانده‌نشده');
    }
}
