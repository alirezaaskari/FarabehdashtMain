<?php

declare(strict_types=1);

namespace App\Modules\Core\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * صفحات خطا.
 *
 * قاعده: صفحه خطا فارسی و راست‌چین است، راه خروج می‌دهد و هرگز ایندکس نمی‌شود.
 */
final class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unknown_address_shows_the_persian_not_found_page(): void
    {
        $response = $this->get('/این-نشانی-وجود-ندارد');

        $response->assertNotFound();
        $response->assertSee('این صفحه پیدا نشد');
        $response->assertSee('lang="fa"', escape: false);
        $response->assertSee('dir="rtl"', escape: false);
    }

    public function test_an_error_page_is_never_indexed(): void
    {
        $this->get('/no-such-page')->assertSee('noindex, nofollow', escape: false);
    }

    public function test_an_error_page_always_offers_a_way_out(): void
    {
        // بن‌بستِ «خطایی رخ داد» کاربر را از سایت بیرون می‌کند.
        $this->get('/no-such-page')->assertSee('بازگشت به صفحه اصلی');
    }

    public function test_the_status_code_is_shown_with_persian_digits(): void
    {
        $this->get('/no-such-page')->assertSee('۴۰۴');
    }
}
