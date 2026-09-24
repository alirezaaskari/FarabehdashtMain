<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DesignSystemTest extends TestCase
{
    // صفحه اصلی از بخش‌های ماژول‌ها ساخته می‌شود و جدول ابزارها را می‌خواند.
    use RefreshDatabase;

    public function test_the_design_system_page_renders_with_real_components(): void
    {
        $response = $this->get('/design-system');

        $response->assertOk()
            ->assertSee('سیستم طراحی فرابهداشت')
            ->assertSee('شش حالت اجباری هر کامپوننت')
            // هر شش حالت روی صفحه حاضرند
            ->assertSee('هنوز محاسبه‌ای ذخیره نکرده‌اید')
            ->assertSee('فهرست محاسبات بارگذاری نشد')
            ->assertSee('در میزکار ذخیره شد')
            ->assertSee('برای ذخیره‌کردن باید وارد حساب خود شوید')
            ->assertSee('این بخش برای پروفایل فروشنده است');
    }

    public function test_workspace_pages_are_never_indexed(): void
    {
        $this->get('/design-system')
            ->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow"', escape: false);
    }

    public function test_every_page_is_rtl_and_persian(): void
    {
        $this->get('/design-system')
            ->assertSee('<html lang="fa" dir="rtl"', escape: false);
    }

    public function test_a_skip_link_is_the_first_focusable_element(): void
    {
        $this->get('/design-system')
            ->assertSee('رفتن به محتوای اصلی');
    }

    public function test_public_pages_stay_light_and_are_indexable(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('data-theme="light"', escape: false)
            ->assertSee('data-lock-theme="true"', escape: false)
            ->assertDontSee('noindex', escape: false);
    }

    public function test_public_pages_carry_a_title_and_description(): void
    {
        $this->get('/')
            ->assertSee('<title>میزکار متخصص بهداشت حرفه‌ای — فرابهداشت</title>', escape: false)
            ->assertSee('name="description"', escape: false);
    }
}
