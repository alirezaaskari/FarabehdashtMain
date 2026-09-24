<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Tests;

use App\Contracts\EntitlementGate;
use App\Models\User;
use App\Modules\Monetization\Actions\AssignTeamSeat;
use App\Modules\Monetization\Actions\RevokeTeamSeat;
use App\Modules\Monetization\Domain\Subscription;
use App\Modules\Monetization\Domain\TeamSeat;
use App\Support\Entitlement\EntitlementReason;
use App\Support\Entitlement\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * صندلی تیمی — فقط صورتحساب و اشتراک‌گذاری، نه پنل سازمانی.
 */
final class TeamSeatTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_seat_gives_the_member_the_same_access(): void
    {
        $member = User::factory()->create();

        $this->assertSame(
            EntitlementReason::RequiresPro,
            $this->gate()->decide($member, Feature::BuildReport)->reason,
        );

        $this->app->make(AssignTeamSeat::class)->handle($this->subscription(), $member);

        $this->assertSame(
            EntitlementReason::Subscribed,
            $this->gate()->decide($member, Feature::BuildReport)->reason,
        );
    }

    public function test_revoking_a_seat_takes_the_access_back_without_deleting_the_row(): void
    {
        $member = User::factory()->create();
        $seat = $this->app->make(AssignTeamSeat::class)->handle($this->subscription(), $member);

        $this->app->make(RevokeTeamSeat::class)->handle($seat);

        $this->assertSame(
            EntitlementReason::RequiresPro,
            $this->gate()->decide($member, Feature::BuildReport)->reason,
        );
        $this->assertSame(1, TeamSeat::query()->count());
        $this->assertNotNull($seat->refresh()->revoked_at);
    }

    /** صندلی روی اشتراک منقضی هیچ دسترسی‌ای نمی‌دهد. */
    public function test_a_seat_on_an_expired_subscription_gives_nothing(): void
    {
        $member = User::factory()->create();
        $subscription = $this->subscription(Carbon::now()->subDay());

        $this->app->make(AssignTeamSeat::class)->handle($subscription, $member);

        $this->assertSame(
            EntitlementReason::RequiresPro,
            $this->gate()->decide($member, Feature::BuildReport)->reason,
        );
    }

    public function test_granting_a_seat_twice_reuses_the_same_row(): void
    {
        $member = User::factory()->create();
        $subscription = $this->subscription();
        $assign = $this->app->make(AssignTeamSeat::class);

        $seat = $assign->handle($subscription, $member);
        $this->app->make(RevokeTeamSeat::class)->handle($seat);
        $assign->handle($subscription, $member);

        $this->assertSame(1, TeamSeat::query()->count());
        $this->assertNull($seat->refresh()->revoked_at);
    }

    public function test_the_owner_cannot_take_a_seat_of_their_own_subscription(): void
    {
        $subscription = $this->subscription();
        $owner = User::query()->findOrFail($subscription->user_id);

        $this->expectException(RuntimeException::class);

        $this->app->make(AssignTeamSeat::class)->handle($subscription, $owner);
    }

    private function gate(): EntitlementGate
    {
        return $this->app->make(EntitlementGate::class);
    }

    private function subscription(?Carbon $endsAt = null): Subscription
    {
        return Subscription::query()->create([
            'uuid' => (string) Str::uuid7(),
            'user_id' => User::factory()->create()->getKey(),
            'started_at' => Carbon::now()->subDay(),
            'ends_at' => $endsAt ?? Carbon::now()->addMonth(),
        ]);
    }
}
