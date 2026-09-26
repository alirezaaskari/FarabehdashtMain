<?php

declare(strict_types=1);

namespace App\Modules\Tools\Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * به‌خاطر سپردن ورودی‌های قبلی ابزار (بخش ۱۸-۱۱).
 *
 * حافظه خودش در مرورگر است (`resources/js/tool-memory.js`)؛ این‌جا فقط
 * قلاب‌های سمت سرور: نوار پیشنهاد فقط روی فرم تازه، و فرم خروج نشان‌دار تا
 * حافظه با خروج پاک شود.
 */
final class ToolMemoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_fresh_tool_form_offers_the_previous_inputs(): void
    {
        $this->get(route('tools.show', 'wbgt-indoor'))
            ->assertOk()
            ->assertSee('data-tool-memory="wbgt-indoor"', false)
            ->assertSee('data-tool-memory-offer', false)
            ->assertSee('پر کردن فرم');
    }

    public function test_a_calculated_or_prefilled_form_does_not_offer_them(): void
    {
        $this->post(route('tools.calculate', 'wbgt-indoor'), ['natural_wet_bulb' => '25', 'globe' => '35'])
            ->assertOk()
            ->assertDontSee('data-tool-memory-offer', false);

        $this->get(route('tools.show', ['wbgt-indoor', 'globe' => '35']))
            ->assertOk()
            ->assertDontSee('data-tool-memory-offer', false);
    }

    public function test_sign_out_forms_are_marked_so_the_memory_is_cleared(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('tools.index'))
            ->assertOk()
            ->assertSee('data-signout', false);
    }
}
