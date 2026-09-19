<?php

declare(strict_types=1);

namespace App\Modules\Identity\Tests;

use App\Contracts\AuditableEvent;
use App\Contracts\SmsSender;
use App\Models\User;
use App\Modules\Identity\Actions\ReviewProfileRequest;
use App\Modules\Identity\Domain\Enums\ProfileStatus;
use App\Modules\Identity\Domain\Enums\ProfileType;
use App\Modules\Identity\Domain\UserProfile;
use App\Modules\Identity\Events\ProfileActivationRequested;
use App\Modules\Identity\Events\ProfileApproved;
use App\Modules\Identity\Events\ProfileDeactivated;
use App\Modules\Identity\Events\ProfileRejected;
use App\Modules\Identity\Events\UserSignedIn;
use App\Modules\Identity\Services\Sms\FakeSmsSender;
use App\Support\Audit\AuditEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * ماژول Identity رویداد درست منتشر می‌کند.
 *
 * عمداً فقط خود رویداد بررسی می‌شود، نه ردیفی که در دیتابیس می‌نشیند: نوشتن
 * ردیف کار ماژول Core است و این تست‌ها باید با Core غیرفعال هم معنی بدهند.
 *
 * فقط رویدادهای خودمان جعلی می‌شوند تا جریان واقعی ورود (نشست، نگهبان، …)
 * دست‌نخورده بماند.
 */
final class AuditEventsTest extends TestCase
{
    use RefreshDatabase;

    private const OUR_EVENTS = [
        UserSignedIn::class,
        ProfileActivationRequested::class,
        ProfileApproved::class,
        ProfileRejected::class,
        ProfileDeactivated::class,
    ];

    private FakeSmsSender $sms;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake(self::OUR_EVENTS);

        $this->sms = new FakeSmsSender;
        $this->app->instance(SmsSender::class, $this->sms);
    }

    public function test_signing_in_for_the_first_time_is_reported_as_a_registration(): void
    {
        $this->signIn('09121234567');

        $this->assertSame('user.registered', $this->entryOf(UserSignedIn::class)->action);
    }

    public function test_a_returning_user_is_reported_as_a_sign_in(): void
    {
        User::factory()->create(['mobile' => '09121234567']);

        $this->signIn('09121234567');

        $this->assertSame('user.signed_in', $this->entryOf(UserSignedIn::class)->action);
    }

    public function test_the_sign_in_record_never_carries_the_mobile_number(): void
    {
        // دفتر رویداد نباید به نسخه دومی از داده شخصی تبدیل شود.
        $this->signIn('09121234567');

        $encoded = json_encode($this->entryOf(UserSignedIn::class), JSON_UNESCAPED_UNICODE);

        $this->assertIsString($encoded);
        $this->assertStringNotContainsString('09121234567', $encoded);
    }

    public function test_requesting_a_profile_reports_the_user_as_the_actor(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('identity.profiles.activate', ProfileType::Vendor->value));

        $entry = $this->entryOf(ProfileActivationRequested::class);

        $this->assertSame('profile.activation_requested', $entry->action);
        $this->assertSame($user->getKey(), $entry->actorId);
        $this->assertSame(['status' => ProfileStatus::Pending->value], $entry->after);
        $this->assertSame(ProfileType::Vendor->value, $entry->context['type']);
    }

    public function test_approving_reports_the_administrator_and_both_states(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $profile = UserProfile::factory()->for($user)->ofType(ProfileType::Vendor)->create();

        $this->app->make(ReviewProfileRequest::class)->approve($profile, $admin);

        $entry = $this->entryOf(ProfileApproved::class);

        $this->assertSame('profile.approved', $entry->action);
        $this->assertSame($admin->getKey(), $entry->actorId);
        $this->assertSame(['status' => ProfileStatus::Pending->value], $entry->before);
        $this->assertSame(['status' => ProfileStatus::Active->value], $entry->after);
        $this->assertSame($user->getKey(), $entry->context['user_id']);
    }

    public function test_rejecting_keeps_the_administrator_note(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $profile = UserProfile::factory()->for($user)->ofType(ProfileType::Instructor)->create();

        $this->app->make(ReviewProfileRequest::class)->reject($profile, $admin, 'مدرک خوانا نبود.');

        $entry = $this->entryOf(ProfileRejected::class);

        $this->assertSame('profile.rejected', $entry->action);
        $this->assertSame('مدرک خوانا نبود.', $entry->context['note']);
    }

    public function test_deactivating_reports_the_previous_status(): void
    {
        $user = User::factory()->create();
        UserProfile::factory()->for($user)->ofType(ProfileType::Vendor)->active()->create();

        $this->actingAs($user)->post(route('identity.profiles.deactivate', ProfileType::Vendor->value));

        $entry = $this->entryOf(ProfileDeactivated::class);

        $this->assertSame('profile.deactivated', $entry->action);
        $this->assertSame(['status' => ProfileStatus::Active->value], $entry->before);
        $this->assertSame(['status' => ProfileStatus::Disabled->value], $entry->after);
    }

    public function test_every_published_event_can_be_written_to_the_audit_log(): void
    {
        // قرارداد است که پل Core روی آن بسته شده؛ رویدادی که آن را پیاده نکند،
        // بی‌سروصدا از دفتر جا می‌ماند.
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $profile = UserProfile::factory()->for($user)->ofType(ProfileType::Vendor)->create();

        $this->app->make(ReviewProfileRequest::class)->approve($profile, $admin);

        Event::assertDispatched(
            ProfileApproved::class,
            static fn (ProfileApproved $event): bool => $event instanceof AuditableEvent,
        );
    }

    private function signIn(string $mobile): void
    {
        $this->post(route('login'), ['mobile' => $mobile, 'terms' => '1']);
        $this->post(route('identity.verify.store'), ['code' => $this->codeSentTo($mobile)]);
    }

    /** کد خام هیچ‌جا ذخیره نمی‌شود، پس تنها راه خواندنش همان پیامک است. */
    private function codeSentTo(string $mobile): string
    {
        return (string) preg_replace('/\D+/', '', (string) $this->sms->lastMessageTo($mobile));
    }

    /** @param  class-string<AuditableEvent>  $event */
    private function entryOf(string $event): AuditEntry
    {
        $captured = null;

        Event::assertDispatched($event, static function (AuditableEvent $dispatched) use (&$captured): bool {
            $captured = $dispatched->auditEntry();

            return true;
        });

        $this->assertInstanceOf(AuditEntry::class, $captured);

        return $captured;
    }
}
