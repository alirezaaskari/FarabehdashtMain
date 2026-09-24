<?php

declare(strict_types=1);

namespace App\Modules\Monetization\Tests;

use App\Contracts\SalesSwitch;
use App\Modules\Monetization\Actions\ToggleRevenueStream;
use App\Modules\Monetization\Domain\Enums\RevenueStream;
use App\Modules\Monetization\Services\StreamSalesSwitch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * کلیدهای «تک‌فروشی فایل» و «تک‌فروشی دوره» واقعاً صفحه‌های فروش را می‌بندند.
 *
 * پیش‌تر صفحه کلیدها می‌گفت صفحه‌ها پنهان می‌شوند ولی هیچ ماژولی کلید را
 * نمی‌پرسید.
 */
final class SalesSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_this_module_answers_the_sales_switch(): void
    {
        $this->assertInstanceOf(StreamSalesSwitch::class, $this->app->make(SalesSwitch::class));
    }

    public function test_switching_off_file_sales_closes_the_shop(): void
    {
        $this->get(route('commerce.index'))->assertOk();

        $this->app->make(ToggleRevenueStream::class)->handle(RevenueStream::FileSale, false);

        $this->get(route('commerce.index'))
            ->assertRedirect(route('home'))
            ->assertSessionHas('notice');
        $this->get(route('commerce.cart'))->assertRedirect(route('home'));

        // کلید دوره‌ها جداست.
        $this->get(route('courses.index'))->assertOk();
    }

    public function test_switching_off_course_sales_closes_the_catalog_and_reopens_it(): void
    {
        $toggle = $this->app->make(ToggleRevenueStream::class);

        $toggle->handle(RevenueStream::CourseSale, false);
        $this->get(route('courses.index'))->assertRedirect(route('home'));

        $toggle->handle(RevenueStream::CourseSale, true);
        $this->get(route('courses.index'))->assertOk();
    }

    public function test_the_home_page_explains_the_redirect(): void
    {
        $this->app->make(ToggleRevenueStream::class)->handle(RevenueStream::FileSale, false);

        $this->followingRedirects()
            ->get(route('commerce.index'))
            ->assertOk()
            ->assertSee('این بخش فروش موقتاً متوقف است.');
    }

    public function test_an_unknown_stream_is_open(): void
    {
        $this->assertTrue($this->app->make(SalesSwitch::class)->isOpen('not-a-stream'));
    }
}
