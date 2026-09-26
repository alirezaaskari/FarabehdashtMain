<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * راهنمای عمومی نویسندگی: بی‌ورود باز می‌شود، مسیر انتشار با تأیید مدیر را
 * می‌گوید و همان قالب متن و منبع فرم نویسنده را نشان می‌دهد.
 */
final class WritingGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_read_how_to_become_a_writer(): void
    {
        $this->get(route('encyclopedia.writing-guide'))
            ->assertOk()
            ->assertSee('راهنمای نوشتن در دانشنامه')
            ->assertSee('بدون تأیید مدیر هیچ نوشته‌ای منتشر نمی‌شود')
            ->assertSee(route('login'), false);
    }

    public function test_the_homepage_invites_readers_to_write(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('دانسته‌ات را بنویس، با نام خودت منتشر کن.')
            ->assertSee(route('encyclopedia.writing-guide'), false);
    }
}
