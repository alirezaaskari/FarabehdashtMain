<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Tests;

use App\Models\User;
use App\Modules\Monetization\Domain\Enums\BillingCycle;
use App\Modules\Monetization\Domain\Enums\PeriodStatus;
use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Monetization\Domain\Plan;
use App\Modules\Monetization\Domain\Subscription;
use App\Modules\Monetization\Domain\SubscriptionPeriod;
use App\Modules\Monetization\Services\ShutdownPreview;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * چهار عدد پیش از خاموشی — هر کدام با حساب دستی قابل بررسی.
 */
final class ShutdownPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-22 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_the_four_numbers_for_the_pro_subscription(): void
    {
        $monthly = $this->plan('m', BillingCycle::Monthly, 290_000);
        $yearly = $this->plan('y', BillingCycle::Yearly, 2_900_000);

        // دوره سی‌روزه‌ای که ده روزش گذشته: بیست سی‌ام قیمت برمی‌گردد.
        $this->subscriber($monthly, Carbon::now()->subDays(10), Carbon::now()->addDays(20));
        $this->subscriber($yearly, Carbon::now()->subDays(65), Carbon::now()->addDays(300));
        // منقضی: نه مشترک شمرده می‌شود نه بازگشت وجه دارد.
        $this->subscriber($monthly, Carbon::now()->subDays(40), Carbon::now()->subDays(10));

        $summary = $this->app->make(ShutdownPreview::class)->for(RevenueStream::ProSubscription);

        $this->assertSame(2, $summary->activeSubscribers);
        // سالانه به ماه تبدیل می‌شود: ۲٬۹۰۰٬۰۰۰ ÷ ۱۲ = ۲۴۱٬۶۶۶
        $this->assertTrue($summary->monthlyRevenue->equals(Money::toman(290_000 + 241_666)));
        $this->assertSame(3, $summary->hiddenPages);
        // ۲۹۰٬۰۰۰ × ۲۰ ÷ ۳۰ + ۲٬۹۰۰٬۰۰۰ × ۳۰۰ ÷ ۳۶۵
        $this->assertTrue($summary->refundNeeded->equals(Money::toman(193_333 + 2_383_561)));
    }

    public function test_a_sale_stream_has_no_subscribers_or_refunds(): void
    {
        $this->subscriber($this->plan('m', BillingCycle::Monthly, 290_000), Carbon::now()->subDay(), Carbon::now()->addMonth());

        $summary = $this->app->make(ShutdownPreview::class)->for(RevenueStream::FileSale);

        $this->assertSame(0, $summary->activeSubscribers);
        $this->assertTrue($summary->monthlyRevenue->isZero());
        $this->assertTrue($summary->refundNeeded->isZero());
    }

    private function plan(string $slug, BillingCycle $cycle, int $price): Plan
    {
        return Plan::query()->create([
            'slug' => $slug,
            'title' => $slug,
            'billing_cycle' => $cycle,
            'price_toman' => $price,
        ]);
    }

    private function subscriber(Plan $plan, Carbon $startsAt, Carbon $endsAt): void
    {
        $subscription = Subscription::query()->create([
            'uuid' => (string) Str::uuid7(),
            'user_id' => User::factory()->create()->getKey(),
            'plan_id' => $plan->getKey(),
            'started_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        SubscriptionPeriod::query()->create([
            'uuid' => (string) Str::uuid7(),
            'subscription_id' => $subscription->getKey(),
            'plan_id' => $plan->getKey(),
            'billing_cycle' => $plan->billing_cycle,
            'price_toman' => $plan->price_toman,
            'status' => PeriodStatus::Paid,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'paid_at' => $startsAt,
        ]);
    }
}
