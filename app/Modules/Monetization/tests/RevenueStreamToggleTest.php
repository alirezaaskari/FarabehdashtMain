<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Tests;

use App\Models\User;
use App\Modules\Monetization\Actions\ExpireSubscriptions;
use App\Modules\Monetization\Actions\ToggleRevenueStream;
use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Monetization\Domain\Enums\ShutdownPolicy;
use App\Modules\Monetization\Domain\Enums\SubscriptionStatus;
use App\Modules\Monetization\Domain\RevenueStreamToggle;
use App\Modules\Monetization\Domain\Subscription;
use App\Modules\Monetization\Services\StreamRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * سه قاعده ثابت سند کلیدها: هیچ داده‌ای حذف نمی‌شود، هر تغییر ردیف دفتر
 * رویداد می‌سازد، و قطع فوری با وجود مشترک فعال ممنوع است.
 */
final class RevenueStreamToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_toggle_writes_an_audit_row_with_the_actor(): void
    {
        $actor = User::factory()->create();

        $this->toggle()->handle(RevenueStream::ProSubscription, false, ShutdownPolicy::RunToEnd, $actor->getKey());

        $row = DB::table('audit_logs')->where('action', 'monetization.revenue_stream_toggled')->sole();

        $this->assertSame('pro_subscription', $row->subject_id);
        $this->assertSame($actor->getKey(), (int) $row->actor_id);
        $this->assertFalse($this->app->make(StreamRegistry::class)->isEnabled(RevenueStream::ProSubscription));
    }

    public function test_switching_off_deletes_nothing(): void
    {
        $subscription = $this->subscription();

        $this->toggle()->handle(RevenueStream::ProSubscription, false);
        $this->toggle()->handle(RevenueStream::ProSubscription, true);

        $this->assertTrue(Subscription::query()->whereKey($subscription->getKey())->exists());
        $this->assertSame(1, RevenueStreamToggle::query()->count());
        $this->assertTrue($this->app->make(StreamRegistry::class)->isEnabled(RevenueStream::ProSubscription));
    }

    public function test_immediate_shutdown_is_refused_while_anyone_is_subscribed(): void
    {
        $this->subscription();

        try {
            $this->toggle()->handle(RevenueStream::ProSubscription, false, ShutdownPolicy::ImmediateNoRefund);
            $this->fail('قطع فوری با مشترک فعال نباید پذیرفته شود.');
        } catch (RuntimeException) {
        }

        $this->assertTrue($this->app->make(StreamRegistry::class)->isEnabled(RevenueStream::ProSubscription));
        $this->assertSame(0, DB::table('audit_logs')->where('action', 'monetization.revenue_stream_toggled')->count());
    }

    public function test_immediate_shutdown_is_allowed_with_no_subscribers(): void
    {
        $this->toggle()->handle(RevenueStream::ProSubscription, false, ShutdownPolicy::ImmediateNoRefund);

        $this->assertFalse($this->app->make(StreamRegistry::class)->isEnabled(RevenueStream::ProSubscription));
    }

    public function test_a_stream_that_is_not_built_cannot_be_switched_on(): void
    {
        $this->expectException(RuntimeException::class);

        $this->toggle()->handle(RevenueStream::JobPosting, true);
    }

    public function test_expiry_only_marks_subscriptions_whose_period_has_passed(): void
    {
        $past = $this->subscription(Carbon::now()->subDay());
        $current = $this->subscription(Carbon::now()->addMonth());

        $this->assertSame(1, $this->app->make(ExpireSubscriptions::class)->handle());

        $this->assertSame(SubscriptionStatus::Expired, $past->refresh()->status);
        $this->assertSame(SubscriptionStatus::Active, $current->refresh()->status);
    }

    private function toggle(): ToggleRevenueStream
    {
        return $this->app->make(ToggleRevenueStream::class);
    }

    private function subscription(?Carbon $endsAt = null): Subscription
    {
        return Subscription::query()->create([
            'uuid' => (string) Str::uuid7(),
            'user_id' => User::factory()->create()->getKey(),
            'started_at' => Carbon::now()->subMonth(),
            'ends_at' => $endsAt ?? Carbon::now()->addMonth(),
        ]);
    }
}
