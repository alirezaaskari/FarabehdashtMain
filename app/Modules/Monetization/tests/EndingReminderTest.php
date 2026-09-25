<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Tests;

use App\Models\User;
use App\Modules\Monetization\Actions\RemindEndingSubscriptions;
use App\Modules\Monetization\Domain\Subscription;
use App\Modules\Workspace\Domain\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * هفت روز مانده به پایان Pro، مشترک یک‌بار خبردار می‌شود (بخش ۱۸-۲).
 */
final class EndingReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_subscriber_hears_once_a_week_before_the_end(): void
    {
        $soon = $this->subscription(Carbon::now()->addDays(6));
        $this->subscription(Carbon::now()->addDays(20));

        $this->assertSame(1, $this->remind());
        $this->assertSame(0, $this->remind());

        $notification = UserNotification::query()->sole();
        $this->assertSame($soon->user_id, $notification->user_id);
        $this->assertSame('monetization.subscription_ending', $notification->kind);
    }

    public function test_a_renewal_earns_a_new_reminder_for_the_new_end(): void
    {
        $subscription = $this->subscription(Carbon::now()->addDays(3));
        $this->remind();

        $subscription->update(['ends_at' => Carbon::now()->addMonth()]);
        $this->assertSame(0, $this->remind());

        $this->assertSame(1, $this->remind(Carbon::now()->addMonth()->subDays(5)));
    }

    public function test_an_ended_subscription_is_not_reminded(): void
    {
        $this->subscription(Carbon::now()->subDay());

        $this->assertSame(0, $this->remind());
    }

    private function remind(?Carbon $at = null): int
    {
        return $this->app->make(RemindEndingSubscriptions::class)->handle($at);
    }

    private function subscription(Carbon $endsAt): Subscription
    {
        return Subscription::query()->create([
            'uuid' => (string) Str::uuid7(),
            'user_id' => User::factory()->create()->getKey(),
            'started_at' => Carbon::now()->subMonth(),
            'ends_at' => $endsAt,
        ]);
    }
}
