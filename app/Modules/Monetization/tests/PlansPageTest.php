<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Tests;

use App\Modules\Monetization\Services\PlanCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * صفحه پلن‌ها: کاربر فقط وقتی می‌خرد که ببیند در برابر رایگان چه می‌گیرد.
 */
final class PlansPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_compares_pro_with_the_free_plan(): void
    {
        $this->get(route('monetization.plans'))
            ->assertOk()
            ->assertSee('رایگان')
            ->assertSee('پیشنهاد ما')
            ->assertSee('اشتراک چه چیزی را باز می‌کند')
            ->assertSee('ذخیره ۵ محاسبه')
            ->assertSee('۱۰٪ تخفیف روی فایل‌های فروشگاه');
    }

    public function test_the_yearly_plan_says_how_many_months_it_saves(): void
    {
        // DEC-14: سالانه ده برابر ماهانه، یعنی دو ماه رایگان.
        $catalog = $this->app->make(PlanCatalog::class);

        $this->assertSame(2, $catalog->yearlyFreeMonths($catalog->active()));

        $this->get(route('monetization.plans'))->assertSee('۲ ماه رایگان');
    }

    public function test_the_upgrade_page_compares_the_blocked_feature_with_pro(): void
    {
        $this->get(route('monetization.upgrade', ['feature' => 'save_calculation']))
            ->assertOk()
            ->assertSee('رایگان در برابر Pro: ذخیره محاسبه')
            ->assertSee('تا ۵ مورد')
            ->assertSee('نامحدود');

        $this->get(route('monetization.upgrade', ['feature' => 'build_report']))
            ->assertOk()
            ->assertSee('ندارد');
    }
}
