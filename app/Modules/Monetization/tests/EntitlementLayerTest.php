<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Tests;

use App\Contracts\EntitlementGate;
use App\Models\User;
use App\Modules\Monetization\Domain\Enums\SubscriptionStatus;
use App\Modules\Monetization\Domain\Subscription;
use App\Support\Entitlement\EntitlementReason;
use App\Support\Entitlement\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * لایه واحد دسترسی — قلب بخش ۱۴.
 *
 * هر تست یک شاخه از ترتیب تصمیم `EntitlementResolver` را می‌سنجد:
 * کلید جریان، اشتراک، سقف رایگان، و «فقط برای مشترک».
 */
final class EntitlementLayerTest extends TestCase
{
    use RefreshDatabase;

    private const INPUTS = ['natural_wet_bulb' => '25', 'globe' => '35'];

    public function test_a_guest_is_asked_to_sign_in(): void
    {
        $decision = $this->gate()->decide(null, Feature::SaveCalculation);

        $this->assertTrue($decision->denied());
        $this->assertSame(EntitlementReason::SignInRequired, $decision->reason);
    }

    public function test_a_free_user_starts_inside_the_quota(): void
    {
        $decision = $this->gate()->decide(User::factory()->create(), Feature::SaveCalculation);

        $this->assertTrue($decision->allowed());
        $this->assertSame(EntitlementReason::WithinFreeQuota, $decision->reason);
        $this->assertSame(0, $decision->used);
        $this->assertSame(5, $decision->limit);
        $this->assertSame(5, $decision->remaining());
    }

    public function test_the_sixth_save_is_refused_and_leads_to_the_upgrade_gate(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->save($user)->assertRedirectContains('/tools/calculations/');
        }

        $decision = $this->gate()->decide($user, Feature::SaveCalculation);
        $this->assertTrue($decision->denied());
        $this->assertSame(EntitlementReason::QuotaExhausted, $decision->reason);
        $this->assertSame(0, $decision->remaining());

        $this->save($user)->assertRedirect(
            route('monetization.upgrade', ['feature' => Feature::SaveCalculation->value]),
        );
    }

    public function test_a_feature_without_a_free_quota_is_for_subscribers_only(): void
    {
        $decision = $this->gate()->decide(User::factory()->create(), Feature::BuildReport);

        $this->assertTrue($decision->denied());
        $this->assertSame(EntitlementReason::RequiresPro, $decision->reason);
    }

    public function test_a_subscriber_has_no_quota_at_all(): void
    {
        $user = User::factory()->create();
        $this->subscribe($user);

        for ($i = 0; $i < 7; $i++) {
            $this->save($user)->assertRedirectContains('/tools/calculations/');
        }

        $this->assertSame(EntitlementReason::Subscribed, $this->gate()->decide($user, Feature::SaveCalculation)->reason);
    }

    public function test_an_expired_subscription_gives_nothing(): void
    {
        $user = User::factory()->create();
        $this->subscribe($user, Carbon::now()->subDay());

        $this->assertSame(EntitlementReason::RequiresPro, $this->gate()->decide($user, Feature::BuildReport)->reason);
    }

    /**
     * لغو، دسترسی را همان لحظه نمی‌گیرد: کاربری که پول این دوره را داده تا
     * پایانش مشترک است. سیاست پیش‌فرض خاموشی هم روی همین قاعده سوار است.
     */
    public function test_a_cancelled_subscription_still_works_until_it_ends(): void
    {
        $user = User::factory()->create();
        $this->subscribe($user, Carbon::now()->addDays(10), SubscriptionStatus::Cancelled);

        $this->assertSame(EntitlementReason::Subscribed, $this->gate()->decide($user, Feature::BuildReport)->reason);
    }

    private function gate(): EntitlementGate
    {
        return $this->app->make(EntitlementGate::class);
    }

    private function subscribe(
        User $user,
        ?Carbon $endsAt = null,
        SubscriptionStatus $status = SubscriptionStatus::Active,
    ): void {
        Subscription::query()->create([
            'uuid' => (string) Str::uuid7(),
            'user_id' => $user->getKey(),
            'status' => $status,
            'started_at' => Carbon::now()->subDay(),
            'ends_at' => $endsAt ?? Carbon::now()->addMonth(),
        ]);
    }

    /** ذخیره از همان مسیری که کاربر واقعی می‌رود، نه با ساختن ردیف دستی. */
    private function save(User $user): TestResponse
    {
        return $this->actingAs($user)->post(
            route('tools.calculations.store', 'wbgt-indoor'),
            [...self::INPUTS, 'label' => 'ایستگاه آزمایشی'],
        );
    }
}
