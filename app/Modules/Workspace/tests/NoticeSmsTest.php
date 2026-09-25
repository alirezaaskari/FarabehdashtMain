<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Tests;

use App\Contracts\SmsSender;
use App\Contracts\UserNotifiableEvent;
use App\Models\User;
use App\Modules\Workspace\Actions\SendDueNoticeSms;
use App\Modules\Workspace\Domain\Enums\SmsDeliveryStatus;
use App\Modules\Workspace\Domain\Enums\SmsTopic;
use App\Modules\Workspace\Domain\SmsDelivery;
use App\Modules\Workspace\Domain\SmsPreference;
use App\Modules\Workspace\Domain\UserNotification;
use App\Support\Notifications\UserNotice;
use App\Support\Sms\SmsMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

/**
 * پیامک اعلان‌های مهم (DEC-39): سقف سه در روز، سکوت ۲۲ تا ۸، خاموشی هر گروه.
 */
final class NoticeSmsTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{mobile: string, message: SmsMessage}> */
    private array $sent = [];

    private bool $failing = false;

    protected function setUp(): void
    {
        parent::setUp();

        config(['workspace.sms.enabled' => true]);

        $this->app->instance(SmsSender::class, new class($this->sent, $this->failing) implements SmsSender
        {
            /** @param  list<array{mobile: string, message: SmsMessage}>  $sent */
            public function __construct(private array &$sent, private bool &$failing) {}

            public function send(string $mobile, SmsMessage $message): void
            {
                if ($this->failing) {
                    throw new RuntimeException('سامانه پیامک پاسخ نداد.');
                }

                $this->sent[] = ['mobile' => $mobile, 'message' => $message];
            }
        });

        Carbon::setTestNow(Carbon::parse('2026-10-01 10:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_an_important_notice_is_texted_with_its_title_and_link_only(): void
    {
        $user = User::factory()->create();

        $this->notify($user, 'profile.approved', 'نقش فروشنده شما فعال شد');
        $this->assertSame(1, $this->send());

        $notification = UserNotification::query()->where('user_id', $user->id)->sole();
        $message = $this->sent[0]['message'];

        $this->assertSame($user->mobile, $this->sent[0]['mobile']);
        $this->assertSame(SendDueNoticeSms::TEMPLATE, $message->template);
        $this->assertSame(
            ['نقش فروشنده شما فعال شد', route('workspace.notifications.open', $notification->uuid)],
            $message->values,
        );
        $this->assertStringNotContainsString($user->mobile, $message->text);
        $this->assertSame(SmsDeliveryStatus::Sent, SmsDelivery::query()->sole()->status);
    }

    public function test_routine_notices_stay_in_the_inbox_only(): void
    {
        $user = User::factory()->create();

        $this->notify($user, 'ledger.wallet_debited');
        $this->notify($user, 'reports.issued');

        $this->assertSame(2, UserNotification::query()->where('user_id', $user->id)->count());
        $this->assertSame(0, SmsDelivery::query()->count());
    }

    public function test_nothing_is_texted_between_ten_at_night_and_eight_in_the_morning(): void
    {
        $user = User::factory()->create();
        Carbon::setTestNow(Carbon::parse('2026-10-01 23:15'));

        $this->notify($user, 'projects.calibration_due');

        $this->assertEquals(Carbon::parse('2026-10-02 08:00'), SmsDelivery::query()->sole()->due_at);
        $this->assertSame(0, $this->send(Carbon::parse('2026-10-02 07:59')));
        $this->assertSame(1, $this->send(Carbon::parse('2026-10-02 08:01')));
    }

    public function test_a_user_gets_at_most_three_texts_a_day(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 4) as $_) {
            $this->notify($user, 'ledger.wallet_credited');
        }

        $this->assertSame(3, $this->send());

        $skipped = SmsDelivery::query()->where('status', SmsDeliveryStatus::Skipped)->sole();
        $this->assertSame(SmsDelivery::NOTE_DAILY_LIMIT, $skipped->note);
        $this->assertSame(4, UserNotification::query()->where('user_id', $user->id)->count());

        // فردا سقف از نو شروع می‌شود.
        Carbon::setTestNow(Carbon::parse('2026-10-02 09:00'));
        $this->notify($user, 'ledger.wallet_credited');
        $this->assertSame(1, $this->send());
    }

    public function test_the_limit_is_per_user(): void
    {
        [$first, $second] = User::factory()->count(2)->create()->all();

        foreach (range(1, 3) as $_) {
            $this->notify($first, 'ledger.wallet_credited');
        }
        $this->notify($second, 'ledger.wallet_credited');

        $this->assertSame(4, $this->send());
    }

    public function test_a_text_left_behind_by_a_sleeping_cron_is_dropped(): void
    {
        $this->notify(User::factory()->create(), 'profile.rejected');

        $this->assertSame(0, $this->send(Carbon::parse('2026-10-02 09:00')));
        $this->assertSame(SmsDelivery::NOTE_STALE, SmsDelivery::query()->sole()->note);
    }

    public function test_a_failed_text_never_loses_the_notice(): void
    {
        $user = User::factory()->create();
        $this->notify($user, 'profile.approved');
        $this->failing = true;

        $this->assertSame(0, $this->send());

        $this->assertSame(SmsDeliveryStatus::Failed, SmsDelivery::query()->sole()->status);
        $this->assertSame(1, UserNotification::query()->where('user_id', $user->id)->count());
    }

    public function test_a_muted_topic_is_not_texted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('workspace.notifications.sms'), ['topics' => ['review' => '1', 'money' => '1', 'nonsense' => '1']])
            ->assertRedirect(route('workspace.notifications'));

        $preference = SmsPreference::query()->where('user_id', $user->id)->sole();
        $this->assertTrue($preference->mutes(SmsTopic::Calibration));
        $this->assertFalse($preference->mutes(SmsTopic::Review));

        $this->notify($user, 'projects.calibration_due');
        $this->notify($user, 'profile.approved');

        $this->assertSame([SmsTopic::Review], SmsDelivery::query()->pluck('topic')->all());
    }

    public function test_the_inbox_offers_one_switch_per_topic(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('workspace.notifications'));

        $response->assertOk()->assertSee('پیامک خبرهای مهم');

        foreach (SmsTopic::cases() as $topic) {
            $response->assertSee($topic->label());
        }
    }

    public function test_the_channel_stays_silent_until_the_admin_switches_it_on(): void
    {
        config(['workspace.sms.enabled' => false]);
        $user = User::factory()->create();

        $this->notify($user, 'profile.approved');

        $this->assertSame(0, SmsDelivery::query()->count());
        $this->actingAs($user)->get(route('workspace.notifications'))->assertDontSee('پیامک خبرهای مهم');
    }

    private function send(?Carbon $at = null): int
    {
        return $this->app->make(SendDueNoticeSms::class)->handle($at);
    }

    private function notify(User $user, string $kind, string $title = 'خبر تازه'): void
    {
        event(new readonly class(new UserNotice($user->id, $kind, $title)) implements UserNotifiableEvent
        {
            public function __construct(private UserNotice $notice) {}

            public function userNotices(): array
            {
                return [$this->notice];
            }
        });
    }
}
