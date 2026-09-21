<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Tests;

use App\Modules\Commerce\Domain\CommissionRate;
use App\Modules\Commerce\Services\CommissionService;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * مرجع یگانه کمیسیون (ADR-0003) — نرخ تاریخ‌دار و Snapshot.
 */
final class CommissionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_falls_back_to_the_configured_default_when_no_rate_is_recorded(): void
    {
        $split = (new CommissionService)->split(Money::toman(100_000), 'shop');

        $this->assertSame(2000, $split->rateBp);
        $this->assertSame(20_000, $split->commission->toman);
        $this->assertSame(80_000, $split->vendorAmount->toman);
    }

    public function test_it_uses_the_latest_rate_whose_effective_date_has_arrived(): void
    {
        CommissionRate::query()->create(['flow' => 'shop', 'rate_bp' => 1500, 'effective_from' => Carbon::yesterday()]);
        CommissionRate::query()->create(['flow' => 'shop', 'rate_bp' => 3000, 'effective_from' => Carbon::tomorrow()]);

        $split = (new CommissionService)->split(Money::toman(100_000), 'shop');

        $this->assertSame(1500, $split->rateBp, 'نرخ فردا هنوز اثر نکرده است.');
        $this->assertSame(15_000, $split->commission->toman);
    }

    public function test_rates_of_other_flows_do_not_interfere(): void
    {
        CommissionRate::query()->create(['flow' => 'course', 'rate_bp' => 1000, 'effective_from' => Carbon::yesterday()]);

        $split = (new CommissionService)->split(Money::toman(100_000), 'shop');

        $this->assertSame(2000, $split->rateBp, 'نرخ جریان دوره نباید روی فروشگاه اثر بگذارد.');
    }

    public function test_the_split_always_sums_back_to_the_original_amount(): void
    {
        CommissionRate::query()->create(['flow' => 'shop', 'rate_bp' => 1333, 'effective_from' => Carbon::yesterday()]);

        $amount = Money::toman(77_777);
        $split = (new CommissionService)->split($amount, 'shop');

        $this->assertSame($amount->toman, $split->commission->toman + $split->vendorAmount->toman);
    }
}
